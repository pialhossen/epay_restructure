#!/bin/bash

# Detect the Windows host IP (WSL 2 gateway)
WINDOWS_IP=$(ip route | grep default | awk '{print $3}')

# Update DB_HOST in .env file
if grep -q "^DB_HOST=" .env; then
  sed -i "s/^DB_HOST=.*/DB_HOST=${WINDOWS_IP}/" .env
else
  echo "DB_HOST=${WINDOWS_IP}" >> .env
fi

echo "✅ .env updated with DB_HOST=${WINDOWS_IP}"
