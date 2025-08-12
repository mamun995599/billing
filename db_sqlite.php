<?php
// Set timezone to Asia/Dhaka
date_default_timezone_set('Asia/Dhaka');

// Database configuration for SQLite
$dbFile = 'clinic_bill.db'; // SQLite database file
try {
    // Create (open) the database file
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign keys
    $pdo->exec("PRAGMA foreign_keys = ON");
    
    // Create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (
        patient_id INTEGER PRIMARY KEY AUTOINCREMENT,
        old_id TEXT,
        date TEXT,
        patient_name TEXT,
        sex TEXT,
        age TEXT,
        phone TEXT,
        ref_doctors TEXT,
        ref_name TEXT,
        delivery_date TEXT,
        delivery_time TEXT,
        remarks TEXT,
        less_total REAL,
        less_percent_total REAL,
        paid REAL,
        send_sms INTEGER,
        created_at TEXT,
        updated_at TEXT,
        address TEXT
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS billing (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_id INTEGER,
        service_id TEXT,
        service_name TEXT,
        price REAL,
        unit INTEGER,
        less_percent REAL,
        less REAL,
        final_price REAL,
        FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        service_id TEXT PRIMARY KEY,
        service_name TEXT NOT NULL,
        price REAL NOT NULL
    )");
    
} catch (PDOException $e) {
    // Handle error
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}