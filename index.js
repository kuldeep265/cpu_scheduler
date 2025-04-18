const express = require('express');
const app = express();
const path = require('path');
app.use(express.static('public'));
app.use(express.json());
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/index.html'));
});
app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/auth/login.html'));
});
app.get('/cart', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/cart.html'));
});
app.get('/register', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/auth/register.html'));
});
app.get('/farmer/dashboard', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/farmer/dashboard.html'));
});
app.get('/farmer/editproduct/:id', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/farmer/editproduct.html'));
});
app.get('/farmer/reviews/:id', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/farmer/reviews.html'));
});
app.get('/farmer/myproducts', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/farmer/myproducts.html'));
});
app.get('/farmer/orders', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/farmer/orders.html'));
});
app.get('/addproduct', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/farmer/addproduct.html'));
});
app.get('/admin/dashboard', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/admin/dashboard.html'));
});
app.get('/admin/users', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/admin/users.html'));
});
app.get('/admin/products', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/admin/products.html'));
});
app.get('/admin/orders', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/admin/orders.html'));
});
app.get('/placeorder', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/placeorder.html'));
});
app.get('/myorders', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/myorders.html'));
});
const PORT = 1006;
app.listen(PORT, () => {
  console.log(`Frontend server running at http://localhost:${PORT}`);
});
