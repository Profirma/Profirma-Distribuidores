<?php
session_start();

if (isset($_SESSION['distribuidor_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PRO-FIRMA | Portal de Distribuidores</title>

    <meta name="description"
          content="Portal exclusivo para distribuidores y compradores mayoristas de PRO-FIRMA.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        :root {
            --pf-navy: #07396b;
            --pf-navy-dark: #052d55;
            --pf-blue: #0b4d8d;
            --pf-light-blue: #85b7e9;
            --pf-cream: #FFE5B4;

            --pf-green: #22c55e;
            --pf-red: #ef4444;

            --bg: #f8fafc;
            --white: #ffffff;

            --text: #0f172a;
            --text-soft: #475569;
            --border: #e2e8f0;

            --font: 'Manrope',
                    -apple-system,
                    BlinkMacSystemFont,
                    'Segoe UI',
                    sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
        }

        button,
        input {
            font-family: var(--font);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* =========================================
           HEADER
        ========================================= */

        header {
            position: sticky;
            top: 0;
            z-index: 1000;

            background: rgba(255,255,255,.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);

            border-bottom: 1px solid rgba(0,0,0,.05);

            box-shadow:
                0 4px 20px rgba(0,0,0,.04);
        }

        .header-content {
            min-height: 82px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 30px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;

            color: var(--pf-navy);
        }

        .brand-icon {
            width: 48px;
            height: 48px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    var(--pf-navy),
                    var(--pf-blue)
                );

            color: white;

            font-size: 18px;
            font-weight: 800;

            box-shadow:
                0 8px 22px rgba(7,57,107,.22);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .brand-name {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .brand-name span {
            color: var(--pf-blue);
        }

        .brand-subtitle {
            margin-top: 6px;

            font-size: 9px;
            font-weight: 700;

            letter-spacing: 1.5px;
            text-transform: uppercase;

            color: #94a3b8;
        }

        nav ul {
            list-style: none;

            display: flex;
            align-items: center;
            gap: 28px;
        }

        nav a {
            color: var(--text);
            font-size: 14px;
            font-weight: 700;

            transition: .25s ease;
        }

        nav a:hover {
            color: var(--pf-navy);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            border: 0;
            border-radius: 14px;

            padding: 13px 22px;

            font-size: 14px;
            font-weight: 800;

            cursor: pointer;

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                background .25s ease;
        }

        .btn-primary {
            color: white;

            background:
                linear-gradient(
                    135deg,
                    var(--pf-navy),
                    var(--pf-blue)
                );

            box-shadow:
                0 8px 25px rgba(7,57,107,.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);

            box-shadow:
                0 13px 32px rgba(7,57,107,.30);
        }

        .btn-light {
            color: var(--pf-navy);

            background: white;

            border: 1px solid rgba(255,255,255,.5);

            box-shadow:
                0 10px 25px rgba(0,0,0,.08);
        }

        .mobile-toggle {
            display: none;

            border: 0;
            background: transparent;

            font-size: 25px;
            color: var(--pf-navy);

            cursor: pointer;
        }

        /* =========================================
           HERO
        ========================================= */

        .hero {
            position: relative;
            overflow: hidden;

            padding: 90px 0 110px;

            color: white;

            background:
                radial-gradient(
                    circle at 88% 10%,
                    rgba(133,183,233,.95) 0%,
                    rgba(133,183,233,.42) 18%,
                    #0a467e 42%,
                    #07396b 72%,
                    #052d55 100%
                );
        }

        .hero::before {
            content: '';

            position: absolute;

            width: 430px;
            height: 430px;

            right: -150px;
            top: -160px;

            border-radius: 50%;

            background: rgba(255,255,255,.12);

            filter: blur(25px);
        }

        .hero::after {
            content: '';

            position: absolute;

            width: 300px;
            height: 300px;

            left: -160px;
            bottom: -180px;

            border-radius: 50%;

            background: rgba(133,183,233,.15);

            filter: blur(30px);
        }

        .hero-grid {
            position: relative;
            z-index: 2;

            display: grid;

            grid-template-columns:
                1.15fr
                .85fr;

            gap: 55px;

            align-items: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 9px;

            padding: 8px 18px;

            border-radius: 30px;

            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.28);

            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);

            font-size: 13px;
            font-weight: 700;

            margin-bottom: 22px;
        }

        .badge i {
            color: #86efac;
        }

        .hero h1 {
            max-width: 700px;

            font-size: clamp(38px, 5vw, 58px);

            line-height: 1.08;

            letter-spacing: -2px;

            font-weight: 800;

            margin-bottom: 24px;
        }

        .hero h1 span {
            color: white;

            background:
                linear-gradient(
                    180deg,
                    #ffffff 0%,
                    #85b7e9 100%
                );

            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            max-width: 650px;

            color: var(--pf-cream);

            font-size: 17px;

            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .hero-points {
            display: flex;
            flex-wrap: wrap;

            gap: 12px;

            margin-top: 30px;
        }

        .hero-point {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 7px 14px;

            border-radius: 25px;

            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.22);

            font-size: 12px;
            font-weight: 700;

            color: #fff;
        }

        .hero-point i {
            color: #86efac;
        }

        /* =========================================
           LOGIN CARD
        ========================================= */

        .login-card {
            background: rgba(255,255,255,.97);

            color: var(--text);

            border-radius: 28px;

            padding: 34px;

            border: 1px solid rgba(255,255,255,.8);

            box-shadow:
                0 30px 70px rgba(0,0,0,.25);
        }

        .login-header {
            text-align: center;

            margin-bottom: 25px;
        }

        .login-icon {
            width: 66px;
            height: 66px;

            margin: 0 auto 17px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 20px;

            color: white;

            background:
                linear-gradient(
                    135deg,
                    var(--pf-navy),
                    var(--pf-blue)
                );

            font-size: 27px;

            box-shadow:
                0 12px 28px rgba(7,57,107,.22);
        }

        .login-header h2 {
            font-size: 23px;
            font-weight: 800;

            color: var(--text);
        }

        .login-header p {
            margin-top: 5px;

            font-size: 13px;

            color: var(--text-soft);
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;

            margin-bottom: 7px;

            font-size: 12px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: .6px;

            color: #334155;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;

            left: 15px;
            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;
        }

        .field input {
            width: 100%;

            padding: 14px 15px 14px 44px;

            border-radius: 13px;

            border: 1px solid var(--border);

            background: #f8fafc;

            color: var(--text);

            outline: none;

            font-size: 14px;

            transition: .2s ease;
        }

        .field input:focus {
            background: white;

            border-color: var(--pf-light-blue);

            box-shadow:
                0 0 0 4px rgba(133,183,233,.15);
        }

        .login-submit {
            width: 100%;

            margin-top: 6px;
        }

        .login-help {
            margin-top: 17px;

            text-align: center;

            font-size: 11px;

            color: #94a3b8;
        }

        .login-help i {
            color: var(--pf-green);
        }

        /* =========================================
           TRUST BAR
        ========================================= */

        .trust-wrapper {
            position: relative;
            z-index: 10;

            margin-top: -42px;
        }

        .trust-bar {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 0;

            background: rgba(255,255,255,.96);

            border-radius: 22px;

            border: 1px solid var(--border);

            box-shadow:
                0 20px 45px rgba(7,57,107,.10);

            overflow: hidden;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 15px;

            padding: 25px 28px;

            border-right: 1px solid var(--border);
        }

        .trust-item:last-child {
            border-right: 0;
        }

        .trust-icon {
            flex-shrink: 0;

            width: 50px;
            height: 50px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 15px;

            background: rgba(133,183,233,.18);

            color: var(--pf-navy);

            font-size: 20px;
        }

        .trust-item strong {
            display: block;

            font-size: 14px;
            font-weight: 800;
        }

        .trust-item span {
            display: block;

            margin-top: 2px;

            color: var(--text-soft);

            font-size: 11px;
        }

        /* =========================================
           SECTION GENERAL
        ========================================= */

        section.content-section {
            padding: 90px 0;
        }

        .section-heading {
            text-align: center;

            max-width: 700px;

            margin: 0 auto 50px;
        }

        .section-heading .eyebrow {
            color: var(--pf-navy);

            font-size: 12px;
            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 1.6px;

            margin-bottom: 10px;
        }

        .section-heading h2 {
            color: var(--pf-navy);

            font-size: clamp(30px, 4vw, 40px);

            line-height: 1.15;

            letter-spacing: -1.2px;

            font-weight: 800;
        }

        .section-heading p {
            margin-top: 12px;

            color: var(--text-soft);

            font-size: 15px;
        }

        /* =========================================
           FEATURES
        ========================================= */

        .features-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 25px;
        }

        .feature-card {
            background: white;

            border: 1px solid var(--border);

            border-radius: 24px;

            padding: 32px 27px;

            box-shadow:
                0 10px 30px rgba(0,0,0,.03);

            transition: .3s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);

            border-color: rgba(7,57,107,.25);

            box-shadow:
                0 20px 45px rgba(7,57,107,.10);
        }

        .feature-icon {
            width: 58px;
            height: 58px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    rgba(7,57,107,.12),
                    rgba(133,183,233,.25)
                );

            color: var(--pf-navy);

            font-size: 23px;

            margin-bottom: 21px;
        }

        .feature-card h3 {
            color: var(--pf-navy);

            font-size: 18px;

            margin-bottom: 9px;

            font-weight: 800;
        }

        .feature-card p {
            color: var(--text-soft);

            font-size: 14px;
        }

        /* =========================================
           PROCESS
        ========================================= */

        .process-section {
            background:
                linear-gradient(
                    180deg,
                    #ffffff 0%,
                    #f1f5f9 100%
                );
        }

        .process-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 25px;
        }

        .process-card {
            position: relative;

            background: rgba(255,255,255,.85);

            border: 1px solid rgba(255,255,255,.95);

            border-radius: 24px;

            padding: 32px 28px;

            box-shadow:
                0 10px 25px rgba(0,0,0,.04);
        }

        .process-number {
            width: 48px;
            height: 48px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 14px;

            background: var(--pf-navy);

            color: white;

            font-size: 19px;
            font-weight: 800;

            margin-bottom: 21px;

            box-shadow:
                0 7px 18px rgba(7,57,107,.25);
        }

        .process-card h3 {
            color: var(--pf-navy);

            font-size: 18px;

            margin-bottom: 8px;
        }

        .process-card p {
            color: var(--text-soft);

            font-size: 14px;
        }

        /* =========================================
           CTA
        ========================================= */

        .cta-section {
            padding: 85px 0;
        }

        .cta {
            position: relative;
            overflow: hidden;

            border-radius: 30px;

            padding: 55px;

            color: white;

            background:
                radial-gradient(
                    circle at 90% 0%,
                    rgba(133,183,233,.65),
                    transparent 32%
                ),
                linear-gradient(
                    135deg,
                    var(--pf-navy),
                    var(--pf-navy-dark)
                );

            box-shadow:
                0 25px 60px rgba(7,57,107,.18);
        }

        .cta-content {
            position: relative;
            z-index: 2;

            max-width: 700px;
        }

        .cta h2 {
            font-size: 34px;

            line-height: 1.15;

            margin-bottom: 13px;
        }

        .cta p {
            color: var(--pf-cream);

            font-size: 15px;

            margin-bottom: 25px;
        }

        /* =========================================
           FOOTER
        ========================================= */

        footer {
            padding: 55px 0 25px;

            background:
                linear-gradient(
                    145deg,
                    var(--pf-navy),
                    var(--pf-navy-dark)
                );

            color: white;
        }

        .footer-grid {
            display: grid;

            grid-template-columns:
                2fr 1fr 1fr;

            gap: 45px;

            padding-bottom: 35px;

            border-bottom:
                1px solid rgba(255,255,255,.15);
        }

        .footer-brand {
            font-size: 22px;
            font-weight: 800;

            margin-bottom: 12px;
        }

        .footer-col p,
        .footer-col a {
            color: var(--pf-cream);

            font-size: 13px;
        }

        .footer-col h4 {
            font-size: 15px;

            margin-bottom: 15px;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col li {
            margin-bottom: 8px;
        }

        .footer-col a:hover {
            color: white;
        }

        .copyright {
            padding-top: 22px;

            text-align: center;

            color: rgba(255,255,255,.55);

            font-size: 11px;
        }

        /* =========================================
           WHATSAPP
        ========================================= */

        .whatsapp {
            position: fixed;

            right: 25px;
            bottom: 25px;

            z-index: 1100;

            width: 58px;
            height: 58px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #25D366;

            color: white;

            font-size: 29px;

            box-shadow:
                0 9px 25px rgba(37,211,102,.30);

            transition: .25s ease;
        }

        .whatsapp:hover {
            transform: scale(1.08);
        }

        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 992px) {

            .hero-grid {
                grid-template-columns: 1fr;
            }

            .hero-copy {
                text-align: center;
            }

            .hero-description {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-buttons,
            .hero-points {
                justify-content: center;
            }

            .features-grid,
            .process-grid {
                grid-template-columns: 1fr 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {

            .header-content {
                min-height: 74px;
            }

            nav {
                display: none;

                position: absolute;

                top: 74px;
                left: 0;

                width: 100%;

                background: rgba(255,255,255,.98);

                box-shadow:
                    0 12px 25px rgba(0,0,0,.08);
            }

            nav.active {
                display: block;
            }

            nav ul {
                flex-direction: column;

                align-items: stretch;

                gap: 0;

                padding: 18px;
            }

            nav li {
                border-bottom: 1px solid var(--border);
            }

            nav li:last-child {
                border-bottom: 0;
            }

            nav a {
                display: block;

                padding: 13px 4px;
            }

            .mobile-toggle {
                display: block;
            }

            .header-button {
                display: none;
            }

            .hero {
                padding: 65px 0 90px;
            }

            .hero h1 {
                letter-spacing: -1.2px;
            }

            .login-card {
                padding: 27px 22px;
            }

            .trust-bar {
                grid-template-columns: 1fr;
            }

            .trust-item {
                border-right: 0;

                border-bottom: 1px solid var(--border);
            }

            .trust-item:last-child {
                border-bottom: 0;
            }

            .features-grid,
            .process-grid,
            .footer-grid {
                grid-template-columns: 1fr;
            }

            section.content-section {
                padding: 70px 0;
            }

            .cta {
                padding: 35px 25px;
            }

            .cta h2 {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {

            .container {
                width: 92%;
            }

            .brand-name {
                font-size: 21px;
            }

            .brand-subtitle {
                font-size: 8px;
            }

            .brand-icon {
                width: 42px;
                height: 42px;
            }

            .hero-buttons .btn {
                width: 100%;
            }

            .hero h1 {
                font-size: 35px;
            }

            .hero-description {
                font-size: 15px;
            }

            .whatsapp {
                right: 18px;
                bottom: 18px;

                width: 53px;
                height: 53px;
            }
        }

    </style>
</head>

<body>

<!-- =========================================
     HEADER
========================================= -->

<header>

    <div class="container header-content">

        <a href="#inicio" class="brand">

            <div class="brand-icon">
                PF
            </div>

            <div class="brand-text">

                <div class="brand-name">
                    PRO<span>-FIRMA</span>
                </div>

                <div class="brand-subtitle">
                    Portal de Distribuidores
                </div>

            </div>

        </a>

        <button
            type="button"
            class="mobile-toggle"
            id="mobileToggle"
            aria-label="Abrir menú">

            <i class="fas fa-bars"></i>

        </button>

        <nav id="mainNav">

            <ul>

                <li>
                    <a href="#inicio">Inicio</a>
                </li>

                <li>
                    <a href="#beneficios">Beneficios</a>
                </li>

                <li>
                    <a href="#proceso">Cómo funciona</a>
                </li>

                <li>
                    <a href="#acceso">Acceso</a>
                </li>

            </ul>

        </nav>

        <a
            href="#acceso"
            class="btn btn-primary header-button">

            <i class="fas fa-right-to-bracket"></i>
            INGRESAR

        </a>

    </div>

</header>


<!-- =========================================
     HERO
========================================= -->

<section class="hero" id="inicio">

    <div class="container hero-grid">

        <div class="hero-copy">

            <div class="badge">

                <i class="fas fa-building"></i>

                Portal exclusivo para compradores mayoristas

            </div>

            <h1>

                Crece con
                <span>PRO-FIRMA</span>
                como distribuidor

            </h1>

            <p class="hero-description">

                Accede a condiciones especiales para compras por mayor,
                administra tu cuenta y gestiona tus operaciones desde
                un solo portal.

            </p>

            <div class="hero-buttons">

                <a
                    href="#acceso"
                    class="btn btn-light">

                    <i class="fas fa-right-to-bracket"></i>

                    INGRESAR AL PORTAL

                </a>

                <a
                    href="#beneficios"
                    class="btn"
                    style="
                        color:white;
                        border:1px solid rgba(255,255,255,.35);
                        background:rgba(255,255,255,.10);
                    ">

                    <i class="fas fa-circle-info"></i>

                    CONOCER MÁS

                </a>

            </div>

            <div class="hero-points">

                <span class="hero-point">
                    <i class="fas fa-check-circle"></i>
                    Compras mayoristas
                </span>

                <span class="hero-point">
                    <i class="fas fa-check-circle"></i>
                    Gestión de saldo
                </span>

                <span class="hero-point">
                    <i class="fas fa-check-circle"></i>
                    Historial de operaciones
                </span>

            </div>

        </div>


        <!-- LOGIN -->

        <div class="login-card" id="acceso">

            <div class="login-header">

                <div class="login-icon">
                    <i class="fas fa-user-tie"></i>
                </div>

                <h2>
                    Acceso de Distribuidores
                </h2>

                <p>
                    Ingresa a tu cuenta de comprador mayorista
                </p>

            </div>

            <form
                action="login.php"
                method="POST"
                autocomplete="on">

                <div class="field">

                    <label for="usuario">
                        Usuario o correo electrónico
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-user"></i>

                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            placeholder="Ingresa tu usuario"
                            autocomplete="username"
                            required>

                    </div>

                </div>


                <div class="field">

                    <label for="password">
                        Contraseña
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-lock"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required>

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary login-submit">

                    INGRESAR AL PORTAL

                    <i class="fas fa-arrow-right"></i>

                </button>

            </form>

            <div class="login-help">

                <i class="fas fa-shield-halved"></i>

                Acceso privado para distribuidores registrados

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     TRUST BAR
========================================= -->

<div class="container trust-wrapper">

    <div class="trust-bar">

        <div class="trust-item">

            <div class="trust-icon">
                <i class="fas fa-store"></i>
            </div>

            <div>

                <strong>
                    Compras por mayor
                </strong>

                <span>
                    Condiciones especiales para distribuidores
                </span>

            </div>

        </div>


        <div class="trust-item">

            <div class="trust-icon">
                <i class="fas fa-wallet"></i>
            </div>

            <div>

                <strong>
                    Control de saldo
                </strong>

                <span>
                    Consulta y administra tus recursos
                </span>

            </div>

        </div>


        <div class="trust-item">

            <div class="trust-icon">
                <i class="fas fa-clock-rotate-left"></i>
            </div>

            <div>

                <strong>
                    Historial
                </strong>

                <span>
                    Consulta tus movimientos y compras
                </span>

            </div>

        </div>

    </div>

</div>


<!-- =========================================
     BENEFICIOS
========================================= -->

<section
    class="content-section"
    id="beneficios">

    <div class="container">

        <div class="section-heading">

            <div class="eyebrow">
                PRO-FIRMA DISTRIBUIDORES
            </div>

            <h2>
                Todo lo que necesitas para comprar por mayor
            </h2>

            <p>
                Un portal pensado exclusivamente para compradores
                mayoristas y distribuidores.
            </p>

        </div>


        <div class="features-grid">


            <article class="feature-card">

                <div class="feature-icon">
                    <i class="fas fa-cart-shopping"></i>
                </div>

                <h3>
                    Compras mayoristas
                </h3>

                <p>
                    Accede a productos y condiciones especiales
                    diseñadas para operaciones por volumen.
                </p>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    <i class="fas fa-tags"></i>
                </div>

                <h3>
                    Precio distribuidor
                </h3>

                <p>
                    Visualiza las condiciones correspondientes a
                    tu cuenta de distribuidor.
                </p>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    <i class="fas fa-wallet"></i>
                </div>

                <h3>
                    Saldo y recargas
                </h3>

                <p>
                    Consulta tu saldo y posteriormente podrás
                    gestionar tus recargas desde el portal.
                </p>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    <i class="fas fa-receipt"></i>
                </div>

                <h3>
                    Historial de compras
                </h3>

                <p>
                    Consulta tus operaciones y movimientos
                    realizados desde tu cuenta.
                </p>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    <i class="fas fa-user-shield"></i>
                </div>

                <h3>
                    Cuenta privada
                </h3>

                <p>
                    Cada distribuidor tendrá su propio acceso
                    y sus datos separados de los clientes normales.
                </p>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    <i class="fas fa-plug"></i>
                </div>

                <h3>
                    Integración PRO-FIRMA
                </h3>

                <p>
                    El portal está diseñado para trabajar con la
                    infraestructura y servicios existentes de PRO-FIRMA.
                </p>

            </article>

        </div>

    </div>

</section>


<!-- =========================================
     PROCESO
========================================= -->

<section
    class="content-section process-section"
    id="proceso">

    <div class="container">

        <div class="section-heading">

            <div class="eyebrow">
                SIMPLE Y DIRECTO
            </div>

            <h2>
                Así funcionará tu portal
            </h2>

            <p>
                Una experiencia sencilla para administrar tus
                compras como distribuidor.
            </p>

        </div>


        <div class="process-grid">


            <article class="process-card">

                <div class="process-number">
                    1
                </div>

                <h3>
                    Ingresa a tu cuenta
                </h3>

                <p>
                    Utiliza tus credenciales de distribuidor
                    para entrar al portal privado.
                </p>

            </article>


            <article class="process-card">

                <div class="process-number">
                    2
                </div>

                <h3>
                    Realiza tu compra
                </h3>

                <p>
                    Consulta los productos disponibles,
                    cantidades y condiciones para mayoristas.
                </p>

            </article>


            <article class="process-card">

                <div class="process-number">
                    3
                </div>

                <h3>
                    Administra tus operaciones
                </h3>

                <p>
                    Consulta tus compras, saldo y movimientos
                    desde tu panel privado.
                </p>

            </article>

        </div>

    </div>

</section>


<!-- =========================================
     CTA
========================================= -->

<section class="cta-section">

    <div class="container">

        <div class="cta">

            <div class="cta-content">

                <h2>
                    ¿Ya eres distribuidor de PRO-FIRMA?
                </h2>

                <p>
                    Ingresa a tu portal privado y administra
                    tus operaciones de compra por mayor.
                </p>

                <a
                    href="#acceso"
                    class="btn btn-light">

                    <i class="fas fa-right-to-bracket"></i>

                    INGRESAR AHORA

                </a>

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     FOOTER
========================================= -->

<footer>

    <div class="container">

        <div class="footer-grid">


            <div class="footer-col">

                <div class="footer-brand">
                    PRO-FIRMA
                </div>

                <p>
                    Portal exclusivo para distribuidores y
                    compradores mayoristas de PRO-FIRMA.
                </p>

            </div>


            <div class="footer-col">

                <h4>
                    Portal
                </h4>

                <ul>

                    <li>
                        <a href="#inicio">
                            Inicio
                        </a>
                    </li>

                    <li>
                        <a href="#beneficios">
                            Beneficios
                        </a>
                    </li>

                    <li>
                        <a href="#proceso">
                            Cómo funciona
                        </a>
                    </li>

                    <li>
                        <a href="#acceso">
                            Acceso
                        </a>
                    </li>

                </ul>

            </div>


            <div class="footer-col">

                <h4>
                    Distribuidores
                </h4>

                <ul>

                    <li>
                        <a href="#acceso">
                            Iniciar sesión
                        </a>
                    </li>

                    <li>
                        <a href="#beneficios">
                            Compras mayoristas
                        </a>
                    </li>

                    <li>
                        <a href="#beneficios">
                            Gestión de cuenta
                        </a>
                    </li>

                </ul>

            </div>

        </div>


        <div class="copyright">

            © <?php echo date('Y'); ?>
            PRO-FIRMA. Todos los derechos reservados.

        </div>

    </div>

</footer>


<!-- =========================================
     WHATSAPP
========================================= -->

<a
    href="#acceso"
    class="whatsapp"
    title="Portal de distribuidores">

    <i class="fas fa-user-tie"></i>

</a>


<!-- =========================================
     JAVASCRIPT
========================================= -->

<script>

    const mobileToggle =
        document.getElementById('mobileToggle');

    const mainNav =
        document.getElementById('mainNav');


    if (mobileToggle && mainNav) {

        mobileToggle.addEventListener(
            'click',
            function () {

                mainNav.classList.toggle('active');

                const icon =
                    mobileToggle.querySelector('i');

                if (
                    mainNav.classList.contains('active')
                ) {

                    icon.classList.remove('fa-bars');

                    icon.classList.add('fa-xmark');

                } else {

                    icon.classList.remove('fa-xmark');

                    icon.classList.add('fa-bars');

                }

            }
        );


        mainNav
            .querySelectorAll('a')
            .forEach(function(link) {

                link.addEventListener(
                    'click',
                    function() {

                        mainNav.classList.remove('active');

                        const icon =
                            mobileToggle.querySelector('i');

                        icon.classList.remove('fa-xmark');

                        icon.classList.add('fa-bars');

                    }
                );

            });

    }

</script>

</body>
</html>
