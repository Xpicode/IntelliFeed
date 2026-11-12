@echo off
"C:\xampp\mysql\bin\mysql.exe" -u root intellifeed -e "DELETE FROM arduino_data WHERE timestamp < NOW() - INTERVAL 48 HOUR;"