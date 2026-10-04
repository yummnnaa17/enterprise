const express = require('express');
const cors = require('cors');
const productRoutes = require('./routes/productRoutes');

const app = express();

app.use(cors());
app.use(express.json({ limit: '3mb' }));

// endpoint for healtcheck
app.get('/health', (req, res) => {
  res.json({
    status: "ok",
    service: "product-service"
  });
});

app.use("/products", productRoutes);

// uknown path
app.use((req, res) => {
  res.status(404).json({
    message: "Endpoint tidak dikenal"

  });
});

module.exports = app;