<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>KimFresh - Orders</title>
    <!-- Make sure to fix CSS paths like this: -->
    <link rel="stylesheet" href="/php-web/css/theme.css"> 
    <!-- ... paste your <style> block here ... -->
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">...</aside>
        <div class="main-area">
            <header class="top-header">...</header>
            <main class="main-content">