# Dockerfile
# Resep image untuk menjalankan review-service (Flask) di dalam container.

FROM python:3.12-slim

# Supaya log langsung tampil di docker compose logs tanpa tertahan buffer.
ENV PYTHONUNBUFFERED=1

WORKDIR /app

# requirements.txt disalin lebih dulu supaya instalasi dependency bisa di-cache Docker.
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Salin kode proyek ke dalam container.
COPY . .

EXPOSE 3002

CMD ["python", "run.py"]