@extends('admin.layout')

@section('title', 'إدارة الأعمال الفنية')
@section('page-title', 'إدارة الأعمال الفنية')

@section('content')
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="mb-3">
                <i class="fas fa-images fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['total'] }}</h3>
            <p class="mb-0">إجمالي الأعمال</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
            <div class="mb-3">
                <i class="fas fa-check-circle fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['available'] }}</h3>
            <p class="mb-0">متاحة للبيع</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
            <div class="mb-3">
                <i class="fas fa-shopping-bag fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['sold'] }}</h3>
            <p class="mb-0">مُباعة</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
            <div class="mb-3">
                <i class="fas fa-clock fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['pending'] }}</h3>
            <p class="mb-0">في الانتظار</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i>
            فلاتر البحث
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.artworks.index') }}">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">البحث</label>
                    <input type="text" name="search" class="form-control" 
                           value="{{ request('search') }}" placeholder="ابحث في العنوان أو الوصف...">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>متاح</option>
                        <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>مُباع</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">الفئة</label>
                    <select name="category" class="form-select">
                        <option value="">جميع الفئات</option>
                        <option value="painting" {{ request('category') == 'painting' ? 'selected' : '' }}>الرسم</option>
                        <option value="sculpture" {{ request('category') == 'sculpture' ? 'selected' : '' }}>النحت</option>
                        <option value="photography" {{ request('category') == 'photography' ? 'selected' : '' }}>التصوير</option>
                        <option value="digital_art" {{ request('category') == 'digital_art' ? 'selected' : '' }}>الفن الرقمي</option>
                        <option value="traditional_art" {{ request('category') == 'traditional_art' ? 'selected' : '' }}>الفن التراثي</option>
                        <option value="calligraphy" {{ request('category') == 'calligraphy' ? 'selected' : '' }}>الخط العربي</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">حالة الفنان</label>
                    <select name="artist_status" class="form-select">
                        <option value="">جميع الفنانين</option>
                        <option value="pending" {{ request('artist_status') == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                        <option value="approved" {{ request('artist_status') == 'approved' ? 'selected' : '' }}>معتمد</option>
                        <option value="rejected" {{ request('artist_status') == 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">ترتيب حسب</label>
                    <select name="sort" class="form-select">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>الأحدث</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>الأقدم</option>
                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>السعر (الأعلى)</option>
                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>السعر (الأقل)</option>
                        <option value="most_viewed" {{ request('sort') == 'most_viewed' ? 'selected' : '' }}>الأكثر مشاهدة</option>
                    </select>
                </div>
                <div class="col-md-1 mb-3 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="clearFilters()" title="مسح الفلاتر">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Artworks Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>
            قائمة الأعمال الفنية
            <span class="badge bg-light text-dark ms-2">{{ $artworks->total() }}</span>
        </h5>
    </div>
    <div class="card-body">
        @if($artworks->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>العنوان</th>
                            <th>الفنان</th>
                            <th>السعر</th>
                            <th>الفئة</th>
                            <th>الحالة</th>
                            <th>المشاهدات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($artworks as $artwork)
                            <tr>
                                <td>
                                    @if($artwork->images && count($artwork->images) > 0)
                                        <img src="{{ url($artwork->images[0]) }}" 
                                             alt="{{ $artwork->title }}" 
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px; border-radius: 8px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ Str::limit($artwork->title, 40) }}</strong>
                                    <br>
                                    <small class="text-muted">{{ Str::limit($artwork->description, 50) }}</small>
                                </td>
                                <td>
                                    {{ $artwork->artist->user->name }}
                                    <br>
                                    <small class="text-muted">{{ $artwork->artist->city ?? 'غير محدد' }}</small>
                                </td>
                                <td>
                                    <strong class="text-primary">{{ number_format($artwork->price) }} ريال</strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $artwork->category }}</span>
                                </td>
                                <td>
                                    @if($artwork->status === 'available')
                                        <span class="badge approved-badge">متاح</span>
                                    @elseif($artwork->status === 'sold')
                                        <span class="badge" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">مُباع</span>
                                    @else
                                        <span class="badge pending-badge">في الانتظار</span>
                                    @endif
                                </td>
                                <td>
                                    <i class="fas fa-eye me-1"></i>
                                    {{ number_format($artwork->views) }}
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.artworks.show', $artwork) }}" 
                                           class="btn btn-outline-primary" title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                    data-bs-toggle="dropdown">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form action="{{ route('admin.artworks.update-status', $artwork) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="available">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-check me-2"></i>متاح
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('admin.artworks.update-status', $artwork) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="pending">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-clock me-2"></i>في الانتظار
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('admin.artworks.update-status', $artwork) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="sold">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-shopping-bag me-2"></i>مُباع
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $artworks->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">لا توجد أعمال فنية</h5>
                <p class="text-muted">جرب تغيير معايير البحث</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="GET"]');
    const searchInput = document.querySelector('input[name="search"]');
    const statusSelect = document.querySelector('select[name="status"]');
    const categorySelect = document.querySelector('select[name="category"]');
    const artistStatusSelect = document.querySelector('select[name="artist_status"]');
    const sortSelect = document.querySelector('select[name="sort"]');
    
    // Debounce function for search input
    let searchTimeout;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            submitForm();
        }, 500); // Wait 500ms after user stops typing
    }
    
    // Submit form function
    function submitForm() {
        // Show loading indicator
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
        loadingIndicator.style.cssText = 'background: rgba(0,0,0,0.3); z-index: 9999;';
        loadingIndicator.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">جاري التحميل...</span></div>';
        document.body.appendChild(loadingIndicator);
        
        form.submit();
    }
    
    // Auto-submit on filter change
    if (statusSelect) {
        statusSelect.addEventListener('change', submitForm);
    }
    if (categorySelect) {
        categorySelect.addEventListener('change', submitForm);
    }
    if (artistStatusSelect) {
        artistStatusSelect.addEventListener('change', submitForm);
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', submitForm);
    }
    
    // Debounced search input
    if (searchInput) {
        searchInput.addEventListener('input', debounceSearch);
    }
    
    // Clear filters function
    window.clearFilters = function() {
        if (searchInput) searchInput.value = '';
        if (statusSelect) statusSelect.value = '';
        if (categorySelect) categorySelect.value = '';
        if (artistStatusSelect) artistStatusSelect.value = '';
        if (sortSelect) sortSelect.value = 'latest';
        submitForm();
    };
});
</script>
@endpush
