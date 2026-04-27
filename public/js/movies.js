// ==================== GLOBAL STATE ====================

let allMovies = [];
let filteredMovies = [];
let cart = [];

// ==================== INITIALIZATION ====================

document.addEventListener('DOMContentLoaded', () => {
	// Only load CSV and setup filters on home page
	if (document.getElementById('movies-table')) {
		loadCSVData();
		setupEventListeners();
	}
	
	// Update cart badge on all pages
	updateCartBadge();
});

// ==================== CSV LOADING ====================

function loadCSVData() {
	fetch('/materials/movies.csv')
		.then(response => response.text())
		.then(data => {
			Papa.parse(data, {
				header: true,
				skipEmptyLines: true,
				complete: (results) => {
					allMovies = results.data;
					filteredMovies = [...allMovies];
					populateFilters();
					displayMovies(filteredMovies);
				},
				error: (error) => {
					console.error('CSV parsing error:', error);
					showNotification('Error loading movies. Please refresh the page.', 'error');
				}
			});
		})
		.catch(error => {
			console.error('Fetch error:', error);
			showNotification('Error loading movies. Please refresh the page.', 'error');
		});
}

// ==================== FILTER POPULATION ====================

function populateFilters() {
	// Get unique genres
	const genres = new Set();
	allMovies.forEach(movie => {
		if (movie.Zanr) {
			// Handle multiple genres separated by comma
			const genreList = movie.Zanr.split(',').map(g => g.trim());
			genreList.forEach(genre => genres.add(genre));
		}
	});

	// Populate genre filter
	const genreSelect = document.getElementById('genre-filter');
	Array.from(genres).sort().forEach(genre => {
		const option = document.createElement('option');
		option.value = genre;
		option.textContent = genre;
		genreSelect.appendChild(option);
	});

	// Get unique countries
	const countries = new Set();
	allMovies.forEach(movie => {
		if (movie.Zemlja_porijekla) {
			// Handle multiple countries separated by /
			const countryList = movie.Zemlja_porijekla.split('/').map(c => c.trim());
			countryList.forEach(country => countries.add(country));
		}
	});

	// Populate country filter
	const countrySelect = document.getElementById('country-filter');
	Array.from(countries).sort().forEach(country => {
		const option = document.createElement('option');
		option.value = country;
		option.textContent = country;
		countrySelect.appendChild(option);
	});
}

// ==================== EVENT LISTENERS ====================

function setupEventListeners() {
	document.getElementById('apply-filters-btn').addEventListener('click', applyFilters);
	document.getElementById('reset-filters-btn').addEventListener('click', resetFilters);
	document.getElementById('genre-filter').addEventListener('change', applyFilters);
	document.getElementById('country-filter').addEventListener('change', applyFilters);
	document.getElementById('sort-by').addEventListener('change', applyFilters);
}

// ==================== FILTERING & SORTING ====================

function applyFilters() {
	const genre = document.getElementById('genre-filter').value;
	const yearFrom = parseInt(document.getElementById('year-from').value) || 0;
	const yearTo = parseInt(document.getElementById('year-to').value) || 9999;
	const country = document.getElementById('country-filter').value;
	const ratingFrom = parseFloat(document.getElementById('rating-from').value) || 0;
	const ratingTo = parseFloat(document.getElementById('rating-to').value) || 10;
	const sortBy = document.getElementById('sort-by').value;

	// Filter movies
	filteredMovies = allMovies.filter(movie => {
		const year = parseInt(movie.Godina);
		const rating = parseFloat(movie.Ocjena);

		// Genre filter
		if (genre && !movie.Zanr.includes(genre)) {
			return false;
		}

		// Year filter
		if (year < yearFrom || year > yearTo) {
			return false;
		}

		// Country filter (handle multiple countries separated by /)
		if (country) {
			const movieCountries = movie.Zemlja_porijekla.split('/').map(c => c.trim());
			if (!movieCountries.includes(country)) {
				return false;
			}
		}

		// Rating filter
		if (rating < ratingFrom || rating > ratingTo) {
			return false;
		}

		return true;
	});

	// Sort movies
	if (sortBy) {
		sortMovies(sortBy);
	}

	displayMovies(filteredMovies);
	updateResultsCount();
}

function sortMovies(sortBy) {
	switch (sortBy) {
		case 'year-asc':
			filteredMovies.sort((a, b) => parseInt(a.Godina) - parseInt(b.Godina));
			break;
		case 'year-desc':
			filteredMovies.sort((a, b) => parseInt(b.Godina) - parseInt(a.Godina));
			break;
		case 'rating-asc':
			filteredMovies.sort((a, b) => parseFloat(a.Ocjena) - parseFloat(b.Ocjena));
			break;
		case 'rating-desc':
			filteredMovies.sort((a, b) => parseFloat(b.Ocjena) - parseFloat(a.Ocjena));
			break;
		case 'title-asc':
			filteredMovies.sort((a, b) => a.Naslov.localeCompare(b.Naslov));
			break;
		default:
			break;
	}
}

function resetFilters() {
	document.getElementById('genre-filter').value = '';
	document.getElementById('year-from').value = '';
	document.getElementById('year-to').value = '';
	document.getElementById('country-filter').value = '';
	document.getElementById('rating-from').value = '';
	document.getElementById('rating-to').value = '';
	document.getElementById('sort-by').value = '';

	filteredMovies = [...allMovies];
	displayMovies(filteredMovies);
	updateResultsCount();
}

// ==================== DISPLAY MOVIES ====================

function displayMovies(movies) {
	const tbody = document.getElementById('movies-tbody');
	tbody.innerHTML = '';

	if (movies.length === 0) {
		tbody.innerHTML = `
			<tr>
				<td colspan="7" class="empty-state">
					<div class="empty-state-icon">📽️</div>
					<div>No movies found matching your filters.</div>
				</td>
			</tr>
		`;
		return;
	}

	movies.forEach((movie, index) => {
		const row = document.createElement('tr');
		row.innerHTML = `
			<td>${movie.Naslov}</td>
			<td>${movie.Godina}</td>
			<td>${movie.Zanr}</td>
			<td>${movie.Trajanje_min}</td>
			<td>${movie.Zemlja_porijekla}</td>
			<td><strong>${movie.Ocjena}</strong></td>
			<td>
				<button class="btn-add-cart" onclick="addToCart(${index})">
					Add to Cart
				</button>
			</td>
		`;
		tbody.appendChild(row);
	});
}

// ==================== CART MANAGEMENT ====================

function addToCart(movieIndex) {
	const movie = filteredMovies[movieIndex];
	
	// Check if movie is already in cart
	const exists = cart.some(item => item.Naslov === movie.Naslov);
	if (exists) {
		showNotification('This movie is already in your cart!', 'error');
		return;
	}

	cart.push(movie);
	saveCartToStorage();
	updateCartBadge();
	showNotification(`"${movie.Naslov}" added to cart!`);
}

function removeFromCart(movieTitle) {
	cart = cart.filter(movie => movie.Naslov !== movieTitle);
	saveCartToStorage();
	updateCartBadge();
	
	// Refresh cart display if on cart page
	if (document.getElementById('cart-tbody')) {
		displayCart();
	}
}

function saveCartToStorage() {
	localStorage.setItem('borrowingCart', JSON.stringify(cart));
}

function loadCartFromStorage() {
	const savedCart = localStorage.getItem('borrowingCart');
	if (savedCart) {
		cart = JSON.parse(savedCart);
	}
}

function updateCartBadge() {
	loadCartFromStorage();
	const badge = document.getElementById('cart-badge');
	if (!badge) return; // Badge doesn't exist on this page (e.g., cart.html)
	
	if (cart.length > 0) {
		badge.textContent = cart.length;
		badge.style.display = 'inline-block';
	} else {
		badge.style.display = 'none';
	}
}

function clearCart() {
	cart = [];
	saveCartToStorage();
	updateCartBadge();
}

// ==================== CART PAGE FUNCTIONS ====================

function displayCart() {
	loadCartFromStorage();
	const tbody = document.getElementById('cart-tbody');
	
	if (!tbody) return;

	tbody.innerHTML = '';

	if (cart.length === 0) {
		tbody.innerHTML = `
			<tr>
				<td colspan="4" class="empty-state">
					<div class="empty-state-icon">🛒</div>
					<div>Your cart is empty. <a href="index.html">Browse movies</a></div>
				</td>
			</tr>
		`;
		updateCartSummary();
		return;
	}

	cart.forEach((movie) => {
		const row = document.createElement('tr');
		row.innerHTML = `
			<td>${movie.Naslov}</td>
			<td>${movie.Godina}</td>
			<td>${movie.Zanr}</td>
			<td>
				<button class="btn-remove" onclick="removeFromCart('${movie.Naslov.replace(/'/g, "\\'")}')">
					Remove
				</button>
			</td>
		`;
		tbody.appendChild(row);
	});

	updateCartSummary();
}

function updateCartSummary() {
	const summary = document.getElementById('cart-summary');
	if (summary) {
		summary.textContent = `Total movies in cart: ${cart.length}`;
	}
}

function confirmBorrowing() {
	loadCartFromStorage();
	
	if (cart.length === 0) {
		showNotification('Your cart is empty!', 'error');
		return;
	}

	const message = `You have successfully added ${cart.length} movie${cart.length !== 1 ? 's' : ''} to your cart for a weekend marathon!`;
	
	// Show confirmation
	const confirmed = confirm(message);
	
	if (confirmed) {
		showNotification(message);
		setTimeout(() => {
			clearCart();
			displayCart();
		}, 1500);
	}
}

// ==================== UTILITY FUNCTIONS ====================

function showNotification(message, type = 'success') {
	const notification = document.createElement('div');
	notification.className = `notification ${type}`;
	notification.textContent = message;
	document.body.appendChild(notification);

	// Remove notification after 4 seconds
	setTimeout(() => {
		notification.remove();
	}, 4000);
}

function updateResultsCount() {
	const count = document.getElementById('results-count');
	if (count) {
		const total = allMovies.length;
		const filtered = filteredMovies.length;
		count.textContent = `Showing ${filtered} of ${total} movies`;
	}
}

// ==================== EXPORT FOR CART PAGE ====================

// Make functions accessible from cart.html
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.confirmBorrowing = confirmBorrowing;
window.displayCart = displayCart;