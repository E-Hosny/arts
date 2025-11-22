@extends('admin.layout')

@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')

@section('content')
<div class="row">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-4">
        <div class="stat-card text-center">
            <div class="mb-3">
                <i class="fas fa-clock fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['pending_artists'] }}</h3>
            <p class="mb-0">فنانين في الانتظار</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
            <div class="mb-3">
                <i class="fas fa-check-circle fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['approved_artists'] }}</h3>
            <p class="mb-0">فنانين معتمدين</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
            <div class="mb-3">
                <i class="fas fa-times-circle fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['rejected_artists'] }}</h3>
            <p class="mb-0">فنانين مرفوضين</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
            <div class="mb-3">
                <i class="fas fa-users fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['total_users'] }}</h3>
            <p class="mb-0">إجمالي المستخدمين</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    إجراءات سريعة
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.artists.pending') }}" class="btn btn-primary">
                        <i class="fas fa-eye me-2"></i>
                        مراجعة الفنانين المعلقين
                        @if($stats['pending_artists'] > 0)
                            <span class="badge bg-warning text-dark ms-2">{{ $stats['pending_artists'] }}</span>
                        @endif
                    </a>
                    
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-images me-2"></i>
                        إدارة الأعمال الفنية
                        <small class="text-muted ms-2">(قريباً)</small>
                    </button>
                    
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-shopping-cart me-2"></i>
                        إدارة الطلبات
                        <small class="text-muted ms-2">(قريباً)</small>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    النشاط الأخير
                </h5>
            </div>
            <div class="card-body">
                @php
                    $recentArtists = App\Models\Artist::with('user')
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();
                @endphp
                
                @forelse($recentArtists as $artist)
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            @if($artist->status === 'pending')
                                <span class="badge pending-badge">في الانتظار</span>
                            @elseif($artist->status === 'approved')
                                <span class="badge approved-badge">معتمد</span>
                            @else
                                <span class="badge rejected-badge">مرفوض</span>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $artist->user->name }}</h6>
                            <small class="text-muted">{{ $artist->created_at->diffForHumans() }}</small>
                        </div>
                        <div>
                            <a href="{{ route('admin.artists.review', $artist) }}" class="btn btn-sm btn-outline-primary">
                                عرض
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center">لا توجد أنشطة حديثة</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Recent Artists Chart placeholder -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    إحصائيات التسجيل
                </h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <p class="text-muted">سيتم إضافة الرسوم البيانية قريباً</p>
            </div>
        </div>
    </div>
</div>
@endsection
