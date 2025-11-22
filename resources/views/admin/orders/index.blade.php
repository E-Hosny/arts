@extends('admin.layout')

@section('title', 'إدارة الطلبات')
@section('page-title', 'إدارة الطلبات')

@section('content')
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="mb-3">
                <i class="fas fa-shopping-cart fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['total'] }}</h3>
            <p class="mb-0">إجمالي الطلبات</p>
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
    <div class="col-md-3 mb-3">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
            <div class="mb-3">
                <i class="fas fa-shipping-fast fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['shipped'] }}</h3>
            <p class="mb-0">قيد الشحن</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card text-center" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
            <div class="mb-3">
                <i class="fas fa-check-circle fa-2x"></i>
            </div>
            <h3 class="mb-1">{{ $stats['delivered'] }}</h3>
            <p class="mb-0">تم التسليم</p>
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
        <form method="GET" action="{{ route('admin.orders.index') }}">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">البحث</label>
                    <input type="text" name="search" class="form-control" 
                           value="{{ request('search') }}" placeholder="رقم الطلب، اسم المشتري، أو الفنان...">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">حالة الدفع</label>
                    <select name="payment_status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                        <option value="completed" {{ request('payment_status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                        <option value="failed" {{ request('payment_status') == 'failed' ? 'selected' : '' }}>فاشل</option>
                        <option value="refunded" {{ request('payment_status') == 'refunded' ? 'selected' : '' }}>مسترد</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">حالة الشحن</label>
                    <select name="shipping_status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="pending" {{ request('shipping_status') == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                        <option value="shipped" {{ request('shipping_status') == 'shipped' ? 'selected' : '' }}>تم الشحن</option>
                        <option value="delivered" {{ request('shipping_status') == 'delivered' ? 'selected' : '' }}>تم التسليم</option>
                        <option value="cancelled" {{ request('shipping_status') == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">طريقة الدفع</label>
                    <select name="payment_method" class="form-select">
                        <option value="">جميع الطرق</option>
                        <option value="mada" {{ request('payment_method') == 'mada' ? 'selected' : '' }}>مدى</option>
                        <option value="visa" {{ request('payment_method') == 'visa' ? 'selected' : '' }}>فيزا</option>
                        <option value="mastercard" {{ request('payment_method') == 'mastercard' ? 'selected' : '' }}>ماستركارد</option>
                        <option value="apple_pay" {{ request('payment_method') == 'apple_pay' ? 'selected' : '' }}>أبل باي</option>
                        <option value="tamara" {{ request('payment_method') == 'tamara' ? 'selected' : '' }}>تمارا</option>
                        <option value="tabby" {{ request('payment_method') == 'tabby' ? 'selected' : '' }}>تابي</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">ترتيب حسب</label>
                    <select name="sort" class="form-select">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>الأحدث</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>الأقدم</option>
                        <option value="amount_high" {{ request('sort') == 'amount_high' ? 'selected' : '' }}>المبلغ (الأعلى)</option>
                        <option value="amount_low" {{ request('sort') == 'amount_low' ? 'selected' : '' }}>المبلغ (الأقل)</option>
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

<!-- Orders Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>
            قائمة الطلبات
            <span class="badge bg-light text-dark ms-2">{{ $orders->total() }}</span>
        </h5>
    </div>
    <div class="card-body">
        @if($orders->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>العمل الفني</th>
                            <th>المشتري</th>
                            <th>الفنان</th>
                            <th>المبلغ</th>
                            <th>حالة الدفع</th>
                            <th>حالة الشحن</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>
                                    <strong>#{{ $order->id }}</strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($order->artwork->images && count($order->artwork->images) > 0)
                                            <img src="{{ url($order->artwork->images[0]) }}" 
                                                 alt="{{ $order->artwork->title }}" 
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; margin-left: 10px;">
                                        @endif
                                        <div>
                                            <strong>{{ Str::limit($order->artwork->title, 30) }}</strong>
                                            <br>
                                            <small class="text-muted">{{ number_format($order->artwork->price) }} ريال</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $order->buyer_name }}
                                    <br>
                                    <small class="text-muted">{{ $order->buyer_phone }}</small>
                                </td>
                                <td>
                                    {{ $order->artist->user->name }}
                                    <br>
                                    <small class="text-muted">{{ $order->artist->city ?? 'غير محدد' }}</small>
                                </td>
                                <td>
                                    <strong class="text-primary">{{ number_format($order->total_amount) }} ريال</strong>
                                    <br>
                                    <small class="text-muted">
                                        عمولة: {{ number_format($order->commission) }} ريال
                                    </small>
                                </td>
                                <td>
                                    @if($order->payment_status === 'completed')
                                        <span class="badge approved-badge">مكتمل</span>
                                    @elseif($order->payment_status === 'pending')
                                        <span class="badge pending-badge">في الانتظار</span>
                                    @elseif($order->payment_status === 'failed')
                                        <span class="badge rejected-badge">فاشل</span>
                                    @else
                                        <span class="badge bg-info">مسترد</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->shipping_status === 'delivered')
                                        <span class="badge approved-badge">تم التسليم</span>
                                    @elseif($order->shipping_status === 'shipped')
                                        <span class="badge" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">تم الشحن</span>
                                        @if($order->tracking_number)
                                            <br><small class="text-muted">{{ $order->tracking_number }}</small>
                                        @endif
                                    @elseif($order->shipping_status === 'pending')
                                        <span class="badge pending-badge">في الانتظار</span>
                                    @else
                                        <span class="badge rejected-badge">ملغي</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $order->created_at->format('Y-m-d') }}
                                    <br>
                                    <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.orders.show', $order) }}" 
                                           class="btn btn-outline-primary" title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($order->canBeShipped())
                                            <button type="button" class="btn btn-outline-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#shipModal{{ $order->id }}" 
                                                    title="شحن الطلب">
                                                <i class="fas fa-shipping-fast"></i>
                                            </button>
                                        @endif
                                        @if($order->canBeDelivered())
                                            <form action="{{ route('admin.orders.deliver', $order) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" 
                                                        title="تأكيد التسليم"
                                                        onclick="return confirm('هل أنت متأكد من تأكيد تسليم هذا الطلب؟')">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- Ship Modal -->
                                    <div class="modal fade" id="shipModal{{ $order->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">شحن الطلب #{{ $order->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.orders.ship', $order) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">رقم التتبع</label>
                                                            <input type="text" name="tracking_number" 
                                                                   class="form-control" required 
                                                                   placeholder="أدخل رقم التتبع...">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-primary">تأكيد الشحن</button>
                                                    </div>
                                                </form>
                                            </div>
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
                {{ $orders->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">لا توجد طلبات</h5>
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
    const paymentStatusSelect = document.querySelector('select[name="payment_status"]');
    const shippingStatusSelect = document.querySelector('select[name="shipping_status"]');
    const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
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
    if (paymentStatusSelect) {
        paymentStatusSelect.addEventListener('change', submitForm);
    }
    if (shippingStatusSelect) {
        shippingStatusSelect.addEventListener('change', submitForm);
    }
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', submitForm);
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
        if (paymentStatusSelect) paymentStatusSelect.value = '';
        if (shippingStatusSelect) shippingStatusSelect.value = '';
        if (paymentMethodSelect) paymentMethodSelect.value = '';
        if (sortSelect) sortSelect.value = 'latest';
        submitForm();
    };
});
</script>
@endpush
