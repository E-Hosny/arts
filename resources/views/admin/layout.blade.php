<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة تحكم الأدمن') - منصة الفنون السعودية</title>
    
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 700;
            color: #2c3e50 !important;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            border: none;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border: none;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
        }
        .pending-badge {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        .approved-badge {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        }
        .rejected-badge {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar p-3">
                <div class="text-center mb-4">
                    <h4 class="text-white">
                        <i class="fas fa-palette me-2"></i>
                        منصة الفنون
                    </h4>
                    <small class="text-light">لوحة تحكم الأدمن</small>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                           href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            الرئيسية
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('admin.artists.*') ? 'active' : '' }}" 
                           href="{{ route('admin.artists.pending') }}">
                            <i class="fas fa-users me-2"></i>
                            مراجعة الفنانين
                            @if($pendingCount = App\Models\Artist::where('status', 'pending')->count())
                                <span class="badge bg-warning text-dark ms-2">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#">
                            <i class="fas fa-images me-2"></i>
                            الأعمال الفنية
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#">
                            <i class="fas fa-shopping-cart me-2"></i>
                            الطلبات
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#">
                            <i class="fas fa-chart-bar me-2"></i>
                            التقارير
                        </a>
                    </li>
                </ul>
                
                <div class="mt-auto pt-4">
                    <div class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i>
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        تسجيل الخروج
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('page-title', 'لوحة التحكم')</h1>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Content -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>
