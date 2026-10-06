<?php

require __DIR__ . '/app/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    try {
        if (current_user()) {
            redirect('dashboard.php');
        }
    } catch (Throwable $error) {
        $loginError = 'El acceso no está disponible temporalmente.';
    }
}

$loginError = $loginError ?? '';

if (!empty($_SESSION['login_error'])) {
    $loginError = (string)$_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>PRO-FIRMA | Portal de Aliados</title>

    <meta
        name="description"
        content="Portal de aliados PRO-FIRMA: solicitudes de firma electrónica, recargas y saldo."
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

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

    --font:
        'Manrope',
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


/* =========================================================
   HEADER
========================================================= */

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

    width: 90px;

    height: 58px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: white;

    border-radius: 12px;

    overflow: hidden;
}

.brand-icon img {

    width: 100%;

    height: 100%;

    object-fit: contain;

    display: block;
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


/* =========================================================
   HERO
========================================================= */

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


/* =========================================================
   LOGIN
========================================================= */

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

.login-error {

    margin-bottom: 18px;

    padding: 12px 14px;

    border-radius: 12px;

    background: #fef2f2;

    border: 1px solid #fecaca;

    color: #b91c1c;

    font-size: 12px;

    font-weight: 700;

    text-align: center;
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


/* =========================================================
   TRUST BAR
========================================================= */

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


/* =========================================================
   SECTIONS
========================================================= */

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


/* =========================================================
   FEATURES
========================================================= */

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


/* =========================================================
   PROCESS
========================================================= */

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


/* =========================================================
   CTA
========================================================= */

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


/* =========================================================
   FOOTER
========================================================= */

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


/* =========================================================
   FLOATING BUTTON
========================================================= */

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


/* =========================================================
   RESPONSIVE
========================================================= */

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
<header><div class="container header-content">
<a href="#inicio" class="brand"><div class="brand-icon"><img src="logo.jpeg" alt="PRO-FIRMA"></div><div class="brand-text"><div class="brand-name">PRO<span>-FIRMA</span></div><div class="brand-subtitle">Portal de Aliados</div></div></a>
<button type="button" class="mobile-toggle" id="mobileToggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="mainNav"><i class="fas fa-bars"></i></button>
<nav id="mainNav"><ul><li><a href="#inicio">Inicio</a></li><li><a href="#proceso">Cómo recargar</a></li><li><a href="#contacto">Contacto</a></li></ul></nav>
<a href="#acceso" class="btn btn-primary header-button"><i class="fas fa-right-to-bracket"></i> INGRESAR</a>
</div></header>
<section class="hero" id="inicio"><div class="container hero-grid"><div class="hero-copy">
<div class="badge"><i class="fas fa-file-signature"></i> Distribución de firmas electrónicas</div>
<h1>Tu cuenta de aliado en <span>PRO-FIRMA</span></h1>
<p class="hero-description">Solicita firmas electrónicas para tus clientes, recarga tu saldo por transferencia y consulta tus trámites desde tu cuenta.</p>
<div class="hero-buttons"><a href="#proceso" class="btn btn-light"><i class="fas fa-wallet"></i> CÓMO RECARGAR</a></div>
</div>
        <div class="login-card" id="acceso">

            <div class="login-header">

                <div class="login-icon">

                    <i class="fas fa-user-tie"></i>

                </div>

                <h2>
                    Acceso de Aliados
                </h2>

                <p>
                    Emite firmas y administra tu saldo
                </p>

            </div>


            <?php if ($loginError !== ''): ?>

                <div class="login-error">

                    <i class="fas fa-circle-exclamation"></i>

                    <?= htmlspecialchars(
                        $loginError,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <form
                action="login.php"
                method="POST"
                autocomplete="on"
            >
                <input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>">

                <div class="field">

                    <label for="usuario">
                        Correo electrónico
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-user"></i>

                        <input
                            type="email"
                            id="usuario"
                            name="usuario"
                            placeholder="Ingresa tu correo"
                            autocomplete="username"
                            required
                        >

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
                            required
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary login-submit"
                >

                    INGRESAR AL PORTAL

                    <i class="fas fa-arrow-right"></i>

                </button>

            </form>


            <div class="login-help">

                <i class="fas fa-shield-halved"></i>

                Acceso privado para aliados registrados

            </div>

        </div>

</div></section>
<section class="content-section process-section" id="proceso"><div class="container">
<div class="section-heading"><div class="eyebrow">RECARGAS POR TRANSFERENCIA</div><h2>Acredita saldo en tres pasos</h2></div>
<div class="process-grid">
<article class="process-card"><div class="process-number">1</div><h3>Realiza la transferencia</h3><p>Transfiere a la cuenta de PRO-FIRMA en Banco Pichincha. Solicita los datos bancarios si todavía no los tienes.</p></article>
<article class="process-card"><div class="process-number">2</div><h3>Sube el comprobante</h3><p>Entra a tu cuenta e indica el monto, banco de origen, fecha y referencia. Adjunta el comprobante en JPG, PNG o PDF.</p></article>
<article class="process-card"><div class="process-number">3</div><h3>Recibe la acreditación</h3><p>El administrador verifica que el pago haya llegado y aprueba la recarga. Entonces el saldo queda disponible en tu cuenta.</p></article>
</div></div></section>
<section class="cta-section" id="contacto"><div class="container"><div class="cta"><div class="cta-content">
<h2>¿Quieres ser aliado de PRO-FIRMA?</h2><p>Contáctanos para solicitar tu cuenta de aliado y conocer los requisitos.</p>
<a href="https://wa.me/593997210562?text=Hola%20PRO-FIRMA%2C%20quiero%20solicitar%20una%20cuenta%20de%20aliado.%20%C2%BFCu%C3%A1les%20son%20los%20requisitos%3F" target="_blank" rel="noopener noreferrer" class="btn btn-light"><i class="fab fa-whatsapp"></i> CONTACTAR A PRO-FIRMA</a>
</div></div></div></section>
<footer><div class="container"><div class="footer-grid">
<div class="footer-col"><div class="footer-brand">PRO-FIRMA</div><p>Portal de aliados de firmas electrónicas.</p></div>
<div class="footer-col"><h4>Tu cuenta</h4><ul><li><a href="#acceso">Iniciar sesión</a></li><li><a href="#proceso">Cómo recargar</a></li></ul></div>
<div class="footer-col"><h4>Atención</h4><ul><li><a href="https://wa.me/593997210562?text=Hola%20PRO-FIRMA%2C%20quiero%20solicitar%20una%20cuenta%20de%20aliado.%20%C2%BFCu%C3%A1les%20son%20los%20requisitos%3F" target="_blank" rel="noopener noreferrer">Contactar con nosotros</a></li></ul></div>
</div><div class="copyright">© <?= date('Y') ?> PRO-FIRMA. Todos los derechos reservados.</div></div></footer>
<a href="https://wa.me/593997210562?text=Hola%20PRO-FIRMA%2C%20quiero%20solicitar%20una%20cuenta%20de%20aliado.%20%C2%BFCu%C3%A1les%20son%20los%20requisitos%3F" target="_blank" rel="noopener noreferrer" class="whatsapp" title="Contactar a PRO-FIRMA" aria-label="Contactar a PRO-FIRMA por WhatsApp"><i class="fab fa-whatsapp"></i></a>
<script>
const mobileToggle = document.getElementById('mobileToggle');
const mainNav = document.getElementById('mainNav');
if (mobileToggle && mainNav) {
    function setMenu(open) {
        mainNav.classList.toggle('active', open);
        mobileToggle.setAttribute('aria-expanded', String(open));
        mobileToggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
        const icon = mobileToggle.querySelector('i');
        icon.classList.toggle('fa-bars', !open);
        icon.classList.toggle('fa-xmark', open);
    }
    mobileToggle.addEventListener('click', () => setMenu(!mainNav.classList.contains('active')));
    mainNav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setMenu(false)));
}
</script>
</body>
</html>
