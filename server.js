const express = require('express');
const app = express();

app.set('view engine', 'ejs'); // Postavljanje EJS kao templating enginea

app.use(express.static('public')); // "posluzuje" index.html
// Automatski koristi sve iz mape public

app.listen(3000, () => {
    console.log("Server pokrenut na http://localhost:3000");
});

// Ucitava slike s poslužitelja s poslužitelja iz mape /images
const fs = require('fs');
const path = require('path');

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

// Deploy on Railway
const PORT = process.env.PORT || 3000;
app.get('/', (req, res) => {
    res.send('Pozdrav sa Railway servera!');
});

app.listen(PORT, () => {
    console.log(`Server pokrenut na portu ${PORT}`);
});