<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Motebele Systems POS</title>

    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">

    {{-- Font Awesome --}}
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body>

<div class="page">

    <!-- ===========================
            HEADER
    ============================ -->

    <header class="header">

        <div class="logo-area">

            <img src="{{ asset('images/logo.png') }}" alt="Logo">

            <div>

                <h2>Motebele Systems (PTY) LTD</h2>

                <span>Point Of Sales System</span>

            </div>

        </div>

        <div class="version">

            Version 1.0

        </div>

    </header>


    <!-- ===========================
            HERO
    ============================ -->

    <section class="hero">

        <span class="badge">

            Enterprise Edition

        </span>

        <h1>

            Welcome to Motebele Systems POS

        </h1>

        <p>

            A complete retail management platform integrating
            Sales, Inventory, Accounting and Lekuka E-Invoicing.

        </p>

    </section>

        <section class="status-bar">

        <div class="status">
            <span class="dot"></span>
            System Online
        </div>

        <div class="status">
            <span class="dot"></span>
            Lekuka Connected
        </div>

        <div class="status">
            <span class="dot"></span>
            License Active
        </div>

        <div class="status">
            <span class="dot"></span>
            Secure Connection
        </div>

    </section>


    <!-- ===========================
            LOGIN PORTALS
    ============================ -->

    <section class="portal-grid">

        <!-- Cashier -->

        <div class="portal-card cashier">

            <div class="icon">

                <i class="fa-solid fa-cash-register"></i>

            </div>

            <h2>Cashier Portal</h2>

            <p>

                Process sales, receive payments,
                print receipts and manage your register.

            </p>

            <ul>

                <li>Sales Processing</li>

                <li>Barcode Scanner</li>

                <li>Returns</li>

                <li>Receipt Printing</li>

                <li>Daily Register</li>

            </ul>

            <div class="buttons">

                <a href="{{ url('/cashier/login') }}"
                   class="btn login">

                    Login

                </a>

                <a href="{{ asset('manuals/cashier.pdf') }}"
                   target="_blank"
                   class="btn manual">

                    User Manual

                </a>

            </div>

        </div>


        <!-- Company -->

        <div class="portal-card company">

            <div class="icon">

                <i class="fa-solid fa-building"></i>

            </div>

            <h2>Company Portal</h2>

            <p>

                Manage products, inventory, employees,
                suppliers, customers and reports.

            </p>

            <ul>

                <li>Inventory</li>

                <li>Purchases</li>

                <li>Employees</li>

                <li>Reports</li>

                <li>Accounting</li>

            </ul>

            <div class="buttons">

                <a href="{{ url('/company/login') }}"
                   class="btn login">

                    Login

                </a>

                <a href="{{ asset('manuals/company.pdf') }}"
                   target="_blank"
                   class="btn manual">

                    User Manual

                </a>

            </div>

        </div>


        <!-- Administrator -->

        <div class="portal-card admin">

            <div class="icon">

                <i class="fa-solid fa-user-shield"></i>

            </div>

            <h2>System Administrator</h2>

            <p>

                Configure the system, users,
                licences, branches and platform settings.

            </p>

            <ul>

                <li>User Management</li>

                <li>Licensing</li>

                <li>Companies</li>

                <li>Audit Logs</li>

                <li>Configuration</li>

            </ul>

            <div class="buttons">

                <a href="{{ url('/admin/login') }}"
                   class="btn login">

                    Login

                </a>

                <a href="{{ asset('manuals/admin.pdf') }}"
                   target="_blank"
                   class="btn manual">

                    User Manual

                </a>

            </div>

        </div>

    </section>


    <!-- ===========================
            FOOTER
    ============================ -->

    <footer class="footer">

        <div>

            <h3>Motebele Systems (PTY) LTD</h3>

            <p>

                Smart Business Software
                for African Businesses.

            </p>

        </div>

        <div>

            <h3>Contact Us</h3>

            <p>📞 +266 63825544/58579683</p>

            <p>✉ msilos.l@gmail.com</p>

            <p>📍 Maseru, Lesotho</p>

        </div>

    </footer>

</div>

</body>
</html>