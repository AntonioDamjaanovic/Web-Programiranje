const express = require('express');
const app = express();
const fs = require('fs');
const path = require('path');

app.set('view engine', 'ejs'); // Postavljanje EJS kao templating enginea

// Test route
app.get('/test', (req, res) => {
    res.json({ message: 'Test route working' });
});

// CSV endpoint - serve movies.csv
app.get('/materials/movies.csv', (req, res) => {
    try {
        const filePath = path.resolve(__dirname, 'materials', 'movies.csv');
        console.log('Attempting to serve CSV from:', filePath);
        const csvContent = fs.readFileSync(filePath, 'utf8');
        console.log('CSV content length:', csvContent.length);
        res.setHeader('Content-Type', 'text/csv; charset=utf-8');
        res.setHeader('Content-Disposition', 'inline');
        res.send(csvContent);
    } catch (err) {
        console.error('CSV serving error:', err.message);
        res.status(500).json({ error: 'Failed to serve CSV', details: err.message });
    }
});

// Serve static files from public folder
app.use(express.static('public'));

// Routes
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.get('/gallery', (req, res) => {
    const folderPath = path.join(__dirname, 'public', 'images');
    const files = fs.readdirSync(folderPath);

    const images = files
        .filter(file => /\.(jpe?g|png|gif|webp)$/i.test(file))
        .map((file, index) => ({
            url: `/images/${file}`,
            id: `img-${index + 1}`,
            title: `Image ${index + 1}`
        }));

    res.render('gallery', { images });
});

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Server pokrenut na portu ${PORT}`);
});