<?php
session_start();

/*
 * DASHBOARD DE DISTRIBUIDORES
 *
 * En esta primera versión estamos trabajando solamente
 * el diseño del panel.
 *
 * Más adelante conectaremos:
 * - Usuarios
 * - Base de datos
 * - Saldo real
 * - PayPhone
 * - API de emisión de firmas
 */

// Datos temporales para visualizar el panel.
// Después vendrán de la base de datos.
$nombre_distribuidor = $_SESSION['distribuidor_nombre'] ?? 'Distribuidor';
$empresa = $_SESSION['distribuidor_empresa'] ?? 'Mi empresa';

$saldo = 0.00;
$total_operaciones = 0;
$firmas_emitidas = 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard | PRO-FIRMA Distribuidores</title>

    <meta
        name="description"
        content="Panel de distribuidores PRO-FIRMA"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">

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

            --bg: #f4f7fb;
            --white: #ffffff;

            --text: #0f172a;
            --text-soft: #64748b;

            --border: #e2e8f0;

            --green: #16a34a;
            --orange: #f59e0b;

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

        body {

            font-family: var(--font);

            background: var(--bg);

            color: var(--text);

            min-height: 100vh;

            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
        }

        button,
        input {
            font-family: var(--font);
        }

        /* ========================================
           LAYOUT
        ======================================== */

        .app {

            display: flex;

            min-height: 100vh;
        }

        /* ========================================
           SIDEBAR
        ======================================== */

        .sidebar {

            width: 265px;

            flex-shrink: 0;

            min-height: 100vh;

            background:
                linear-gradient(
                    160deg,
                    var(--pf-navy),
                    var(--pf-navy-dark)
                );

            color: white;

            padding: 25px 18px;

            position: fixed;

            left: 0;
            top: 0;
            bottom: 0;

            z-index: 100;

            transition: .3s ease;
        }

        .logo {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 0 10px 28px;

            border-bottom:
                1px solid rgba(255,255,255,.12);
        }

        .logo-icon {

            width: 45px;
            height: 45px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background:
                rgba(255,255,255,.13);

            border:
                1px solid rgba(255,255,255,.18);

            font-weight: 800;
            font-size: 15px;
        }

        .logo-text {

            display: flex;

            flex-direction: column;

            line-height: 1;
        }

        .logo-name {

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -.5px;
        }

        .logo-subtitle {

            margin-top: 5px;

            font-size: 8px;

            letter-spacing: 1.2px;

            text-transform: uppercase;

            color: #a8c9e8;

            font-weight: 700;
        }

        .menu {

            margin-top: 25px;
        }

        .menu-title {

            padding: 0 12px 10px;

            color: rgba(255,255,255,.45);

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1.2px;

            text-transform: uppercase;
        }

        .menu a {

            display: flex;

            align-items: center;

            gap: 13px;

            padding: 13px 14px;

            margin-bottom: 5px;

            border-radius: 12px;

            color: rgba(255,255,255,.78);

            font-size: 13px;

            font-weight: 700;

            transition: .2s ease;
        }

        .menu a i {

            width: 19px;

            text-align: center;

            font-size: 15px;
        }

        .menu a:hover,
        .menu a.active {

            color: white;

            background:
                rgba(255,255,255,.12);
        }

        .menu a.active {

            box-shadow:
                inset 3px 0 0 #85b7e9;
        }

        .sidebar-bottom {

            position: absolute;

            left: 18px;
            right: 18px;
            bottom: 25px;
        }

        .sidebar-help {

            padding: 17px;

            border-radius: 15px;

            background:
                rgba(255,255,255,.08);

            border:
                1px solid rgba(255,255,255,.09);

            margin-bottom: 13px;
        }

        .sidebar-help strong {

            display: block;

            font-size: 12px;

            margin-bottom: 4px;
        }

        .sidebar-help span {

            color: rgba(255,255,255,.58);

            font-size: 10px;
        }

        .logout {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            width: 100%;

            padding: 11px;

            border-radius: 11px;

            color: rgba(255,255,255,.75);

            border:
                1px solid rgba(255,255,255,.14);

            font-size: 12px;

            font-weight: 700;
        }

        .logout:hover {

            color: white;

            background:
                rgba(255,255,255,.08);
        }

        /* ========================================
           MAIN
        ======================================== */

        .main {

            width: calc(100% - 265px);

            margin-left: 265px;

            min-height: 100vh;
        }

        /* ========================================
           TOPBAR
        ======================================== */

        .topbar {

            height: 78px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 35px;

            background:
                rgba(255,255,255,.94);

            border-bottom:
                1px solid var(--border);

            position: sticky;

            top: 0;

            z-index: 50;

            backdrop-filter: blur(12px);
        }

        .mobile-menu {

            display: none;

            border: 0;

            background: transparent;

            color: var(--pf-navy);

            font-size: 21px;

            cursor: pointer;
        }

        .topbar-title h1 {

            font-size: 19px;

            font-weight: 800;

            color: var(--pf-navy);
        }

        .topbar-title p {

            margin-top: 2px;

            font-size: 11px;

            color: var(--text-soft);
        }

        .profile {

            display: flex;

            align-items: center;

            gap: 11px;
        }

        .profile-info {

            text-align: right;
        }

        .profile-info strong {

            display: block;

            font-size: 12px;

            font-weight: 800;
        }

        .profile-info span {

            font-size: 10px;

            color: var(--text-soft);
        }

        .profile-avatar {

            width: 42px;
            height: 42px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    var(--pf-navy),
                    var(--pf-blue)
                );

            color: white;

            font-weight: 800;

            box-shadow:
                0 6px 18px rgba(7,57,107,.20);
        }

        /* ========================================
           CONTENT
        ======================================== */

        .content {

            padding: 35px;
        }

        .welcome {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;
        }

        .welcome h2 {

            color: var(--pf-navy);

            font-size: 25px;

            font-weight: 800;

            letter-spacing: -.7px;
        }

        .welcome p {

            margin-top: 5px;

            color: var(--text-soft);

            font-size: 13px;
        }

        .date-badge {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 9px 14px;

            border-radius: 12px;

            background: white;

            border: 1px solid var(--border);

            color: var(--text-soft);

            font-size: 11px;

            font-weight: 700;
        }

        /* ========================================
           CARDS
        ======================================== */

        .cards {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }

        .card {

            background: white;

            border:
                1px solid var(--border);

            border-radius: 19px;

            padding: 22px;

            box-shadow:
                0 7px 25px rgba(15,23,42,.035);
        }

        .card-top {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 17px;
        }

        .card-icon {

            width: 44px;
            height: 44px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 13px;

            color: var(--pf-navy);

            background:
                rgba(133,183,233,.20);

            font-size: 18px;
        }

        .card-label {

            color: var(--text-soft);

            font-size: 11px;

            font-weight: 700;
        }

        .card-value {

            color: var(--pf-navy);

            font-size: 25px;

            font-weight: 800;

            letter-spacing: -.7px;
        }

        .card-description {

            margin-top: 5px;

            color: var(--text-soft);

            font-size: 10px;
        }

        .balance-card {

            background:
                linear-gradient(
                    135deg,
                    var(--pf-navy),
                    var(--pf-blue)
                );

            border: 0;

            color: white;

            box-shadow:
                0 13px 35px rgba(7,57,107,.20);
        }

        .balance-card .card-icon {

            color: white;

            background:
                rgba(255,255,255,.14);
        }

        .balance-card .card-label,
        .balance-card .card-description {

            color: rgba(255,255,255,.67);
        }

        .balance-card .card-value {

            color: white;
        }

        .balance-action {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            margin-top: 13px;

            padding: 8px 12px;

            border-radius: 9px;

            color: var(--pf-navy);

            background: white;

            font-size: 10px;

            font-weight: 800;
        }

        /* ========================================
           GRID INFERIOR
        ======================================== */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                1.55fr
                .85fr;

            gap: 20px;
        }

        .panel {

            background: white;

            border:
                1px solid var(--border);

            border-radius: 20px;

            box-shadow:
                0 7px 25px rgba(15,23,42,.035);

            overflow: hidden;
        }

        .panel-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 21px 23px;

            border-bottom:
                1px solid var(--border);
        }

        .panel-header h3 {

            color: var(--pf-navy);

            font-size: 14px;

            font-weight: 800;
        }

        .panel-header a {

            color: var(--pf-blue);

            font-size: 10px;

            font-weight: 800;
        }

        .empty-state {

            padding: 50px 25px;

            text-align: center;
        }

        .empty-icon {

            width: 58px;
            height: 58px;

            display: flex;

            align-items: center;
            justify-content: center;

            margin: 0 auto 15px;

            border-radius: 17px;

            background:
                #f1f5f9;

            color: #94a3b8;

            font-size: 22px;
        }

        .empty-state h4 {

            color: var(--pf-navy);

            font-size: 14px;

            margin-bottom: 5px;
        }

        .empty-state p {

            max-width: 340px;

            margin: 0 auto;

            color: var(--text-soft);

            font-size: 11px;

            line-height: 1.7;
        }

        /* ========================================
           QUICK ACTIONS
        ======================================== */

        .quick-actions {

            padding: 18px;
        }

        .quick-action {

            display: flex;

            align-items: center;

            gap: 13px;

            width: 100%;

            padding: 14px;

            margin-bottom: 9px;

            border:
                1px solid var(--border);

            border-radius: 13px;

            color: var(--text);

            background: white;

            transition: .2s ease;
        }

        .quick-action:last-child {
            margin-bottom: 0;
        }

        .quick-action:hover {

            border-color:
                rgba(7,57,107,.25);

            background:
                #f8fafc;

            transform: translateX(2px);
        }

        .quick-icon {

            width: 38px;
            height: 38px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 11px;

            color: var(--pf-navy);

            background:
                rgba(133,183,233,.18);
        }

        .quick-action strong {

            display: block;

            font-size: 11px;

            font-weight: 800;
        }

        .quick-action span {

            display: block;

            margin-top: 2px;

            color: var(--text-soft);

            font-size: 9px;
        }

        /* ========================================
           SECURITY NOTE
        ======================================== */

        .security {

            margin-top: 20px;

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 15px 18px;

            border-radius: 14px;

            background:
                rgba(22,163,74,.07);

            border:
                1px solid rgba(22,163,74,.12);

            color: #166534;
        }

        .security i {

            font-size: 18px;
        }

        .security strong {

            display: block;

            font-size: 11px;
        }

        .security span {

            display: block;

            margin-top: 2px;

            font-size: 9px;

            color: #4d7c5c;
        }

        /* ========================================
           RESPONSIVE
        ======================================== */

        @media (max-width: 1100px) {

            .cards {

                grid-template-columns:
                    repeat(2, 1fr);
            }

            .dashboard-grid {

                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 850px) {

            .sidebar {

                transform: translateX(-100%);
            }

            .sidebar.open {

                transform: translateX(0);
            }

            .main {

                width: 100%;

                margin-left: 0;
            }

            .mobile-menu {

                display: block;
            }

            .topbar {

                padding: 0 20px;
            }

            .content {

                padding: 25px 20px;
            }

            .welcome {

                align-items: flex-start;

                flex-direction: column;
            }
        }

        @media (max-width: 600px) {

            .cards {

                grid-template-columns: 1fr;
            }

            .profile-info {

                display: none;
            }

            .topbar-title h1 {

                font-size: 16px;
            }

            .welcome h2 {

                font-size: 21px;
            }

            .date-badge {

                display: none;
            }
        }

    </style>

</head>

<body>

<div class="app">


    <!-- ======================================
         SIDEBAR
    ======================================= -->

    <aside class="sidebar" id="sidebar">

        <div class="logo">

            <div class="logo-icon">
                PF
            </div>

            <div class="logo-text">

                <div class="logo-name">
                    PRO-FIRMA
                </div>

                <div class="logo-subtitle">
                    Distribuidores
                </div>

            </div>

        </div>


        <nav class="menu">

            <div class="menu-title">
                Panel
            </div>

            <a
                href="dashboard.php"
                class="active">

                <i class="fas fa-chart-pie"></i>

                <span>
                    Inicio
                </span>

            </a>

            <a href="#">

                <i class="fas fa-wallet"></i>

                <span>
                    Mi saldo
                </span>

            </a>

            <a href="#">

                <i class="fas fa-circle-plus"></i>

                <span>
                    Recargar saldo
                </span>

            </a>

            <a href="#">

                <i class="fas fa-file-signature"></i>

                <span>
                    Solicitar firma
                </span>

            </a>

            <a href="#">

                <i class="fas fa-clock-rotate-left"></i>

                <span>
                    Mis operaciones
                </span>

            </a>


            <div
                class="menu-title"
                style="margin-top:25px;">

                Cuenta
            </div>

            <a href="#">

                <i class="fas fa-user"></i>

                <span>
                    Mi cuenta
                </span>

            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="sidebar-help">

                <strong>
                    ¿Necesitas ayuda?
                </strong>

                <span>
                    Contacta con soporte PRO-FIRMA.
                </span>

            </div>

            <a
                href="logout.php"
                class="logout">

                <i class="fas fa-right-from-bracket"></i>

                Cerrar sesión

            </a>

        </div>

    </aside>


    <!-- ======================================
         MAIN
    ======================================= -->

    <main class="main">


        <!-- TOPBAR -->

        <header class="topbar">

            <div style="display:flex;align-items:center;gap:14px;">

                <button
                    class="mobile-menu"
                    id="mobileMenu"
                    type="button">

                    <i class="fas fa-bars"></i>

                </button>

                <div class="topbar-title">

                    <h1>
                        Panel de Distribuidores
                    </h1>

                    <p>
                        Administración de tu cuenta mayorista
                    </p>

                </div>

            </div>


            <div class="profile">

                <div class="profile-info">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $nombre_distribuidor
                        );
                        ?>
                    </strong>

                    <span>
                        <?php
                        echo htmlspecialchars(
                            $empresa
                        );
                        ?>
                    </span>

                </div>

                <div class="profile-avatar">

                    <?php
                    echo strtoupper(
                        substr(
                            $nombre_distribuidor,
                            0,
                            1
                        )
                    );
                    ?>

                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <div class="content">


            <!-- WELCOME -->

            <div class="welcome">

                <div>

                    <h2>
                        Bienvenido,
                        <?php
                        echo htmlspecialchars(
                            $nombre_distribuidor
                        );
                        ?>
                    </h2>

                    <p>
                        Aquí puedes administrar tus operaciones
                        como distribuidor PRO-FIRMA.
                    </p>

                </div>


                <div class="date-badge">

                    <i class="far fa-calendar"></i>

                    <?php
                    echo date('d/m/Y');
                    ?>

                </div>

            </div>


            <!-- STAT CARDS -->

            <div class="cards">


                <!-- SALDO -->

                <div class="card balance-card">

                    <div class="card-top">

                        <div class="card-icon">

                            <i class="fas fa-wallet"></i>

                        </div>

                        <div class="card-label">
                            SALDO DISPONIBLE
                        </div>

                    </div>

                    <div class="card-value">

                        $
                        <?php
                        echo number_format(
                            $saldo,
                            2
                        );
                        ?>

                    </div>

                    <div class="card-description">
                        Disponible para operaciones
                    </div>

                    <a
                        href="#"
                        class="balance-action">

                        <i class="fas fa-plus"></i>

                        Recargar saldo

                    </a>

                </div>


                <!-- OPERACIONES -->

                <div class="card">

                    <div class="card-top">

                        <div class="card-icon">

                            <i class="fas fa-receipt"></i>

                        </div>

                        <div class="card-label">
                            OPERACIONES
                        </div>

                    </div>

                    <div class="card-value">

                        <?php
                        echo number_format(
                            $total_operaciones
                        );
                        ?>

                    </div>

                    <div class="card-description">
                        Operaciones realizadas
                    </div>

                </div>


                <!-- FIRMAS -->

                <div class="card">

                    <div class="card-top">

                        <div class="card-icon">

                            <i class="fas fa-file-signature"></i>

                        </div>

                        <div class="card-label">
                            FIRMAS
                        </div>

                    </div>

                    <div class="card-value">

                        <?php
                        echo number_format(
                            $firmas_emitidas
                        );
                        ?>

                    </div>

                    <div class="card-description">
                        Firmas emitidas
                    </div>

                </div>


                <!-- ESTADO -->

                <div class="card">

                    <div class="card-top">

                        <div class="card-icon">

                            <i class="fas fa-circle-check"></i>

                        </div>

                        <div class="card-label">
                            CUENTA
                        </div>

                    </div>

                    <div
                        class="card-value"
                        style="
                            font-size:20px;
                            color:#16a34a;
                        ">

                        ACTIVA

                    </div>

                    <div class="card-description">
                        Distribuidor habilitado
                    </div>

                </div>

            </div>


            <!-- LOWER GRID -->

            <div class="dashboard-grid">


                <!-- OPERATIONS -->

                <section class="panel">

                    <div class="panel-header">

                        <h3>
                            Últimas operaciones
                        </h3>

                        <a href="#">
                            Ver historial
                        </a>

                    </div>


                    <div class="empty-state">

                        <div class="empty-icon">

                            <i class="fas fa-receipt"></i>

                        </div>

                        <h4>
                            No hay operaciones todavía
                        </h4>

                        <p>
                            Cuando realices una recarga o solicites
                            una firma, tus operaciones aparecerán
                            aquí.
                        </p>

                    </div>

                </section>


                <!-- QUICK ACTIONS -->

                <section class="panel">

                    <div class="panel-header">

                        <h3>
                            Acciones rápidas
                        </h3>

                    </div>


                    <div class="quick-actions">


                        <a
                            href="#"
                            class="quick-action">

                            <div class="quick-icon">

                                <i class="fas fa-plus"></i>

                            </div>

                            <div>

                                <strong>
                                    Recargar saldo
                                </strong>

                                <span>
                                    Próximamente con PayPhone
                                </span>

                            </div>

                        </a>


                        <a
                            href="#"
                            class="quick-action">

                            <div class="quick-icon">

                                <i class="fas fa-file-signature"></i>

                            </div>

                            <div>

                                <strong>
                                    Solicitar firma
                                </strong>

                                <span>
                                    Emitir una nueva firma
                                </span>

                            </div>

                        </a>


                        <a
                            href="#"
                            class="quick-action">

                            <div class="quick-icon">

                                <i class="fas fa-user"></i>

                            </div>

                            <div>

                                <strong>
                                    Mi cuenta
                                </strong>

                                <span>
                                    Administrar mis datos
                                </span>

                            </div>

                        </a>


                    </div>

                </section>

            </div>


            <!-- SECURITY -->

            <div class="security">

                <i class="fas fa-shield-halved"></i>

                <div>

                    <strong>
                        Portal seguro PRO-FIRMA
                    </strong>

                    <span>
                        Tus operaciones y datos se gestionarán
                        de forma segura.
                    </span>

                </div>

            </div>

        </div>

    </main>

</div>


<script>

    const mobileMenu =
        document.getElementById('mobileMenu');

    const sidebar =
        document.getElementById('sidebar');


    if (mobileMenu && sidebar) {

        mobileMenu.addEventListener(
            'click',
            function () {

                sidebar.classList.toggle('open');

            }
        );

    }

</script>

</body>
</html>
