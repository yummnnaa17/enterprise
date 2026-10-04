const productModel = require('../models/productModel');

function parseId(value) {
    const id = Number(value);
    return Number.isInteger(id) && id > 0 ? id : null;
}

function validateProduct(body) {
    body = body || {};
    const { name, price, stock, image } = body;
    const errors = [];

    if (typeof name !== 'string' || name.trim() === '') {
        errors.push('name wajib berupa teks dan tidak boleh kosong');
    }

    if (typeof price !== 'number' || !Number.isFinite(price) || price < 0) {
        errors.push('price wajib berupa angka >= 0');
    }

    if (!Number.isInteger(stock) || stock < 0) {
        errors.push('stock wajib berupa bilangan bulat >= 0');
    }

    // Validasi image wajib diisi
    if (typeof image !== 'string' || image.trim() === '') {
        errors.push('Field image wajib diisi');
        return errors;
    }

    // Hilangkan prefix data URI jika ada
    let base64Image = image.trim();

    if (base64Image.startsWith('data:image/')) {
        const separatorIndex = base64Image.indexOf('base64,');

        if (separatorIndex === -1) {
            errors.push('Field image harus berupa Base64 yang valid');
            return errors;
        }

        base64Image = base64Image.substring(separatorIndex + 7);
    }

    // Cek karakter Base64
    const base64Regex = /^[A-Za-z0-9+/]+={0,2}$/;

    if (!base64Regex.test(base64Image)) {
        errors.push('Field image harus berupa Base64 yang valid');
        return errors;
    }

    // Panjang Base64 harus kelipatan 4
    if (base64Image.length % 4 !== 0) {
        errors.push('Field image harus berupa Base64 yang valid');
        return errors;
    }

    // Decode Base64 untuk mengetahui ukuran file asli
    let imageBuffer;

    try {
        imageBuffer = Buffer.from(base64Image, 'base64');
    } catch (error) {
        errors.push('Field image harus berupa Base64 yang valid');
        return errors;
    }

    // Maksimal 2 MB
    const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

    if (imageBuffer.length > MAX_IMAGE_SIZE) {
        errors.push('Ukuran image maksimal 2 MB');
    }

    return errors;
}

function productPayload(body) {
    body = body || {};
    return {
        name: body.name.trim(),
        description: body.description == null ? null : String(body.description),
        price: body.price,
        stock: body.stock,
        image: body.image.trim()
    };
}

async function index(req, res) {
    try {
        const products = await productModel.getAllProducts();
        res.status(200).json({ data: products });
    } catch (error) {
        console.error('Failed to list products:', error);
        res.status(500).json({ message: 'Gagal mengambil data product' });
    }
}

async function show(req, res) {
    const id = parseId(req.params.id);
    if (!id) return res.status(400).json({ message: 'ID product tidak valid' });

    try {
        const product = await productModel.getProductById(id);
        if (!product) return res.status(404).json({ message: 'Product tidak ditemukan' });
        res.status(200).json({ data: product });
    } catch (error) {
        console.error('Failed to get product:', error);
        res.status(500).json({ message: 'Gagal mengambil product' });
    }
}

async function store(req, res) {
    const errors = validateProduct(req.body);
    if (errors.length) return res.status(400).json({ message: 'Data product tidak valid', errors });

    try {
        const product = await productModel.createProduct(productPayload(req.body));
        res.status(201).json({ message: 'Product berhasil dibuat', data: product });
    } catch (error) {
        console.error('Failed to create product:', error);
        res.status(500).json({ message: 'Gagal membuat product' });
    }
}

async function update(req, res) {
    const id = parseId(req.params.id);
    if (!id) return res.status(400).json({ message: 'ID product tidak valid' });
    const errors = validateProduct(req.body);
    if (errors.length) return res.status(400).json({ message: 'Data product tidak valid', errors });

    try {
        const existing = await productModel.getProductById(id);
        if (!existing) return res.status(404).json({ message: 'Product tidak ditemukan' });
        const product = await productModel.updateProduct(id, productPayload(req.body));
        res.status(200).json({ message: 'Product berhasil diubah', data: product });
    } catch (error) {
        console.error('Failed to update product:', error);
        res.status(500).json({ message: 'Gagal mengubah product' });
    }
}

async function destroy(req, res) {
    const id = parseId(req.params.id);
    if (!id) return res.status(400).json({ message: 'ID product tidak valid' });

    try {
        const deleted = await productModel.deleteProduct(id);
        if (!deleted) return res.status(404).json({ message: 'Product tidak ditemukan' });
        res.status(200).json({ message: 'Product berhasil dihapus' });
    } catch (error) {
        console.error('Failed to delete product:', error);
        res.status(500).json({ message: 'Gagal menghapus product' });
    }
}

module.exports = { index, show, store, update, destroy };