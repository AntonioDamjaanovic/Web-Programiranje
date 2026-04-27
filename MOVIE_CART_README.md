# Movie Database - Filtering & Cart System

A complete client-side movie filtering and borrowing cart application built with HTML, CSS, and vanilla JavaScript.

## 🎬 Features

### Movie Filtering
- **Filter by Genre**: Select from all available genres
- **Filter by Year Range**: Set "From" and "To" years to narrow results
- **Filter by Country**: Select specific country (handles multiple countries per movie)
- **Filter by Rating**: Set minimum and maximum ratings
- **Sorting**: Sort by year (ascending/descending), rating, or title
- **Real-time Results Count**: Shows how many movies match current filters

### Shopping Cart
- **Add to Cart**: Each movie has an "Add to Cart" button
- **View Cart**: Dedicated cart page with all selected movies
- **Remove Items**: Remove individual movies from the cart
- **Clear Cart**: Remove all movies at once
- **Persistent Storage**: Cart is saved in browser localStorage
- **Cart Badge**: Navigation link shows count of items in cart

### User Experience
- **Dynamic Notifications**: Toast notifications when adding movies
- **Confirmation Dialog**: Confirmation message shows exact count of movies being borrowed
- **Empty States**: Clear messaging when no movies found or cart is empty
- **Responsive Design**: Works on desktop and mobile devices
- **Clean UI**: Professional styling with intuitive filters layout

## 📁 Project Structure

```
Web programiranje/
├── public/
│   ├── index.html           # Main movie browsing page
│   ├── cart.html            # Shopping cart page
│   ├── js/
│   │   └── movies.js        # Main JavaScript application
│   └── styles/
│       ├── style.css        # Base styles
│       └── movies.css       # Movie filtering & cart styles
├── materials/
│   └── movies.csv           # Movie database (CSV format)
├── server.js                # Express.js server
└── package.json             # Dependencies
```

## 🚀 Getting Started

### Prerequisites
- Node.js (v14 or higher)
- npm (comes with Node.js)

### Installation

1. Navigate to the project directory:
```bash
cd "Web programiranje"
```

2. Install dependencies:
```bash
npm install
```

3. Start the server:
```bash
npm start
```

4. Open your browser and navigate to:
```
http://localhost:3000
```

## 💻 How to Use

### Filtering Movies

1. **Go to Home Page**: Click "Home" in the navigation
2. **Set Filters**:
   - Select a genre from the "Genre" dropdown
   - Set year range (optional)
   - Select country (optional)
   - Set rating range (optional)
3. **Apply Filters**: Click "Apply Filters" button
4. **Sort Results** (optional): Select a sort option from "Sort By" dropdown
5. **Reset**: Click "Reset" to clear all filters

### Adding Movies to Cart

1. **Browse** filtered movies in the table
2. **Click** "Add to Cart" button next to desired movie
3. **Confirmation**: See notification confirming movie was added
4. **View Count**: Check cart badge in navigation showing number of items

### Managing Your Cart

1. **View Cart**: Click "My Cart" in the navigation
2. **Review Items**: See all selected movies with details
3. **Remove Items**: Click "Remove" button next to any movie
4. **Clear All**: Click "🗑️ Clear Cart" to remove all items
5. **Confirm Borrowing**: Click "📦 Confirm Borrowing" to finalize

### Confirmation

1. A dialog appears asking to confirm
2. Message shows exact number of movies: *"You have successfully added X movie(s) to your cart for a weekend marathon!"*
3. Accept to clear cart and return to empty cart view
4. Success notification appears

## 📊 CSV Data Format

The application reads movie data from `materials/movies.csv`. The CSV file should have these columns:

- `Naslov` - Movie title
- `Zanr` - Genre(s) (comma-separated)
- `Godina` - Year of release
- `Trajanje_min` - Duration in minutes
- `Ocjena` - Rating (0-10)
- `Redatelj` - Director
- `Zemlja_porijekla` - Country/Countries (slash-separated for multiple)

## 🛠️ Technical Details

### Technologies Used
- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+)
- **CSV Parsing**: PapaParse library
- **Server**: Express.js
- **Storage**: Browser localStorage

### Key JavaScript Functions

#### CSV & Initialization
- `loadCSVData()` - Loads and parses CSV file
- `populateFilters()` - Extracts unique genres and countries

#### Filtering
- `applyFilters()` - Applies all active filters
- `sortMovies()` - Sorts filtered results
- `resetFilters()` - Clears all filters

#### Cart Management
- `addToCart()` - Adds movie to cart array
- `removeFromCart()` - Removes movie from cart
- `clearCart()` - Empties entire cart
- `saveCartToStorage()` - Saves cart to localStorage
- `loadCartFromStorage()` - Loads cart from localStorage

#### Display
- `displayMovies()` - Renders filtered movies table
- `displayCart()` - Renders cart page table
- `updateCartBadge()` - Updates cart count badge
- `showNotification()` - Shows toast notifications

## 🎨 Styling Features

- **Responsive Grid Layout**: Filters adapt to screen size
- **Professional Color Scheme**: Green for primary actions, red for destructive
- **Interactive Elements**: Buttons have hover and active states
- **Animations**: Slide-in/out notifications
- **Mobile Optimized**: Touch-friendly buttons and spacing
- **Accessibility**: Semantic HTML with proper labels and ARIA attributes

## ⚠️ Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## 📝 Notes

- **Multiple Countries**: Movies with multiple countries (e.g., "USA/UK") are properly handled
- **Multiple Genres**: Genres separated by commas are stored and displayed correctly
- **Persistent Cart**: Cart persists even after closing browser (localStorage)
- **Client-Side Only**: All filtering and cart logic runs in the browser
- **No Backend Processing**: Movies are filtered entirely on the client

## 🐛 Troubleshooting

### Movies not loading?
- Ensure server is running (`npm start`)
- Check that `materials/movies.csv` exists
- Clear browser cache and reload

### Cart not persisting?
- Check if localStorage is enabled in browser
- Ensure you're not in private/incognito mode
- Try clearing browser data

### Filters not working?
- Make sure you clicked "Apply Filters" button
- Check that filter values are set correctly
- Try "Reset" to clear all filters and start over

## 📄 License

This project is part of a Web Programming course exercise.

## 👨‍💻 Author

Created for Web Programming course assignment (LV 2 task)

---

**Enjoy browsing and managing your movie collection! 🎬**
