<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Perhitungan SPK pemilihan Supplier Bbat dengan metode SMART</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Tambahkan ini -->
    <style>
    body {
        overflow-x: hidden;
        padding-top: 56px;
    }
    .navbar {
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 1050;
        background: linear-gradient(to right, #6cbced, #007bff); /* Light blue gradient for navbar */
        /* background: black; */
    }
    .sidebar {
        height: calc(100vh - 56px); /* Adjusted to match navbar height */
        background-color: #5bc0de; /* Light Blue Sidebar */
        padding-top: 1rem;
        position: fixed;
        top: 56px;
        left: 0;
        /* width: 250px; */
        transition: all 0.3s ease;
        z-index: 1000;
    }
    .sidebar a {
        color: #fff;
        padding: 10px;
        display: flex;
        align-items: center;
        text-decoration: none;
    }
    .sidebar a i {
        margin-right: 10px;
    }
    .sidebar a span {
        display: inline;
        transition: opacity 0.3s ease;
        opacity: 1;
    }
    .sidebar a:hover {
        background-color: #0056b3; /* Darker blue on hover */
    }
    .content {
        position: absolute;
        top: 56px; /* Adjust based on navbar height */
        left: 250px; /* Adjusted to match sidebar width */
        width: calc(100% - 250px); /* Adjust width to account for sidebar */
        height: calc(100vh - 56px); /* Adjust based on navbar height */
        padding: 2rem; /* Add padding for content */
        transition: all 0.3s ease;
    }

    .sidebar.collapsed {
        width: 70px;
    }
    .sidebar.collapsed a span {
        opacity: 0;
        width: 0;
        overflow: hidden;
        display: none;
    }
    .sidebar.collapsed a i {
        margin-right: 0;
        text-align: center;
        width: 100%;
    }
    .content.collapsed {
        margin-left: 70px;
        width: calc(100% - 70px); /* Adjust width when sidebar is collapsed */
        left: 70px; /* Adjust left property when sidebar is collapsed */
    }

    @media (max-width: 768px) {
        .sidebar {
            left: -250px; /* Hidden by default on mobile */
            box-shadow: 2px 0 5px rgba(0,0,0,0.2);
            /* width: 70px; */ /* Removed: mobile sidebar should be full width (250px) when active */
            padding-top: 3rem;
        }
        .sidebar.active {
            left: 0;
            width: 250px; /* Explicitly set width for active mobile sidebar */
        }
        .content {
            margin-left: 0;
        }
        #toggleSidebar {
            display: block;
        }
        .navbar-brand {
            font-size: 0.8em; /* Even smaller font size */
            white-space: normal; /* Allow text to wrap */
            line-height: 1.2; /* Adjust line height for wrapped text */
            max-width: 60%; /* Limit width to give space to other elements */
        }
        .navbar .d-flex {
            flex-wrap: wrap; /* Allow user info and logout to wrap */
            justify-content: flex-end; /* Align to the right */
            align-items: center;
        }
        .navbar .d-flex .navbar-text {
            font-size: 0.8em; /* Smaller font for username */
            margin-bottom: 5px; /* Add some space if it wraps */
            width: 100%; /* Take full width if wrapped */
            text-align: right;
        }
        .navbar .d-flex .btn {
            font-size: 0.8em; /* Smaller button text */
            padding: 5px 10px; /* Adjust button padding */
        }
    }

    @media (min-width: 769px) {
        .sidebar{
            left: 0;
            width: 250px;
        }
        #toggleSidebar {
            display: block;
        }
        .sidebar:not(.collapsed) a span {
            display: inline;
            opacity: 1;
            width: auto;
        }
        .sidebar:not(.collapsed) a i {
            margin-right: 10px;
        }
    }

    .sidebar a.active {
        background-color: #007bff; /* Light blue active background */
        color: #fff;
        font-weight: bold;
        border-left: 3px solid #f8f9fa;
    }
    .sidebar a.active:hover {
        background-color: #0056b3;
    }
</style>
</head>
<body>

<nav class="navbar navbar-dark sticky-top">
    <div class="container-fluid">
        <button class="btn btn-outline-light" id="toggleSidebar">☰</button>
        <a class="navbar-brand ms-2" href="index.php">Sistem Penunjang Keputusan Pemilihan Supplier Terbaik Metode SMART untuk Grandlucky MOI</a>

        <?php if (isset($_SESSION['user'])): ?>
        <div class="dropdown">
            <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
             <?= $_SESSION['user']['nama_lengkap'] ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</nav>

<div class="sidebar" id="sidebar">
    <a href="index.php"><i class="bi bi-house"></i> <span>Dashboard</span></a>
    <a href="alternatif.php"><i class="bi bi-collection"></i> <span>Alternatif</span></a>
    <a href="kriteria.php"><i class="bi bi-funnel"></i> <span>Kriteria</span></a>
    <a href="nilai.php"><i class="bi bi-clipboard-check"></i> <span>Input Nilai</span></a>
    <a href="proses.php"><i class="bi bi-bar-chart-line"></i> <span>Hasil SMART</span></a>
    <a href="best_supplier.php"><i class="bi bi-bar-chart-line"></i> <span>Pemilihan Supplier Terbaik</span></a>
</div>


<script>
    console.log("Bootstrap loaded:", typeof bootstrap !== 'undefined');
    document.addEventListener('DOMContentLoaded', function() {
        const toggleSidebarBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');

        const currentPath = window.location.pathname;
        const sidebarLinks = sidebar.querySelectorAll('a');

        sidebarLinks.forEach(link => {
            link.classList.remove('active');

            if (currentPath.endsWith(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });

        function closeSidebarOutside(event) {
            if (window.innerWidth < 769 && !sidebar.contains(event.target) && !toggleSidebarBtn.contains(event.target)) {
                sidebar.classList.remove('active');
                document.removeEventListener('click', closeSidebarOutside);
            }
        }

        function handleSidebarToggle() {
            if (window.innerWidth >= 769) { // Desktop behavior
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('collapsed');
            } else { // Mobile behavior
                sidebar.classList.toggle('active');
                if (sidebar.classList.contains('active')) {
                    document.addEventListener('click', closeSidebarOutside);
                } else {
                    document.removeEventListener('click', closeSidebarOutside);
                }
            }
        }

        function adjustSidebarForScreenSize() {
            if (window.innerWidth >= 769) { // Desktop
                sidebar.classList.remove('active'); // Ensure mobile active class is off
                document.removeEventListener('click', closeSidebarOutside); // Remove mobile overlay listener

                if (!sidebar.classList.contains('collapsed')) {
                    content.classList.remove('collapsed');
                    sidebar.classList.remove('collapsed');
                }
            } else { // Mobile
                sidebar.classList.remove('collapsed'); // Ensure desktop collapsed class is off
                content.classList.remove('collapsed'); // Ensure desktop content collapse is off

                if (sidebar.classList.contains('active')) {
                    document.addEventListener('click', closeSidebarOutside);
                } else {
                    document.removeEventListener('click', closeSidebarOutside);
                }
            }
        }

        // Initial setup
        adjustSidebarForScreenSize();
        toggleSidebarBtn.addEventListener('click', handleSidebarToggle);
        window.addEventListener('resize', adjustSidebarForScreenSize);
    });
</script>

<!-- <div class="content" id="content"></div> -->
</body>
</html>