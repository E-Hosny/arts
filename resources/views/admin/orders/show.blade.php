@extends('admin.layout')

@section('title', 'تفاصيل الطلب')
@section('page-title', 'تفاصيل الطلب #' . $order->id)

@section('content')
<div class="row">
    <div class="col-md-8 mb-4">
        <!-- Order Details -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    معلومات الطلب
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>رقم الطلب:</strong> #{{ $order->id }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>تاريخ الطلب:</strong> {{ $order->created_at->format('Y-m-d H:i') }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>المبلغ الإجمالي:</strong> 
                        <span class="text-primary fs-5">{{ number_format($order->total_amount) }} ريال</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>العمولة:</strong> {{ number_format($order->commission) }} ريال
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>أرباح الفنان:</strong> 
                        <span class="text-success">{{ number_format($order->artist_earnings) }} ريال</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>طريقة الدفع:</strong> 
                        <span class="badge bg-secondary">{{ $order->payment_method }}</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>حالة الدفع:</strong>
                        @if($order->payment_status === 'completed')
                            <span class="badge approved-badge">مكتمل</span>
                        @elseif($order->payment_status === 'pending')
                            <span class="badge pending-badge">في الانتظار</span>
                        @elseif($order->payment_status === 'failed')
                            <span class="badge rejected-badge">فاشل</span>
                        @else
                            <span class="badge bg-info">مسترد</span>
                        @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>حالة الشحن:</strong>
                        @if($order->shipping_status === 'delivered')
                            <span class="badge approved-badge">تم التسليم</span>
                        @elseif($order->shipping_status === 'shipped')
                            <span class="badge" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">تم الشحن</span>
                        @elseif($order->shipping_status === 'pending')
                            <span class="badge pending-badge">في الانتظار</span>
                        @else
                            <span class="badge rejected-badge">ملغي</span>
                        @endif
                    </div>
                    @if($order->tracking_number)
                        <div class="col-md-6 mb-3">
                            <strong>رقم التتبع:</strong> 
                            <span class="text-info">{{ $order->tracking_number }}</span>
                        </div>
                    @endif
                    @if($order->delivered_at)
                        <div class="col-md-6 mb-3">
                            <strong>تاريخ التسليم:</strong> 
                            {{ $order->delivered_at->format('Y-m-d H:i') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Artwork Details -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-image me-2"></i>
                    معلومات العمل الفني
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        @if($order->artwork->images && count($order->artwork->images) > 0)
                            <img src="{{ url($order->artwork->images[0]) }}" 
                                 alt="{{ $order->artwork->title }}" 
                                 class="img-fluid rounded">
                        @endif
                    </div>
                    <div class="col-md-8">
                        <h6>{{ $order->artwork->title }}</h6>
                        <p class="text-muted">{{ $order->artwork->description }}</p>
                        <p><strong>الفئة:</strong> {{ $order->artwork->category }}</p>
                        @if($order->artwork->dimensions)
                            <p><strong>الأبعاد:</strong> {{ $order->artwork->dimensions }}</p>
                        @endif
                        @if($order->artwork->materials)
                            <p><strong>المواد:</strong> {{ $order->artwork->materials }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Buyer Details -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    معلومات المشتري
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>الاسم:</strong> {{ $order->buyer_name }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>رقم الهاتف:</strong> {{ $order->buyer_phone }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>البريد الإلكتروني:</strong> {{ $order->buyer->email }}
                    </div>
                    <div class="col-12 mb-3">
                        <strong>عنوان الشحن:</strong>
                        <p class="mb-0">{{ $order->shipping_address }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <!-- Artist Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-palette me-2"></i>
                    معلومات الفنان
                </h6>
            </div>
            <div class="card-body">
                <h6>{{ $order->artist->user->name }}</h6>
                <p class="text-muted mb-2">
                    <i class="fas fa-envelope me-1"></i>
                    {{ $order->artist->user->email }}
                </p>
                <p class="text-muted mb-2">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    {{ $order->artist->city ?? 'غير محدد' }}
                </p>
                <p class="mb-2">
                    <strong>معدل العمولة:</strong> {{ $order->artist->commission_rate }}%
                </p>
                <div class="mt-3">
                    <a href="{{ route('admin.artists.review', $order->artist) }}" 
                       class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-eye me-1"></i>
                        عرض ملف الفنان
                    </a>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    إجراءات الطلب
                </h6>
            </div>
            <div class="card-body">
                @if($order->canBeShipped())
                    <button type="button" class="btn btn-warning w-100 mb-2" 
                            data-bs-toggle="modal" data-bs-target="#shipModal">
                        <i class="fas fa-shipping-fast me-1"></i>
                        شحن الطلب
                    </button>
                @endif

                @if($order->canBeDelivered())
                    <form action="{{ route('admin.orders.deliver', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 mb-2"
                                onclick="return confirm('هل أنت متأكد من تأكيد تسليم هذا الطلب؟')">
                            <i class="fas fa-check-circle me-1"></i>
                            تأكيد التسليم
                        </button>
                    </form>
                @endif

                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-arrow-right me-1"></i>
                    العودة للقائمة
                </a>
            </div>
        </div>

        <!-- Transaction Info -->
        @if($order->transaction)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        معلومات المعاملة
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>رقم المعاملة:</strong> #{{ $order->transaction->id }}
                    </p>
                    <p class="mb-2">
                        <strong>المبلغ الصافي:</strong> 
                        {{ number_format($order->transaction->net_amount) }} ريال
                    </p>
                    <p class="mb-2">
                        <strong>الحالة:</strong>
                        @if($order->transaction->status === 'completed')
                            <span class="badge approved-badge">مكتمل</span>
                        @else
                            <span class="badge pending-badge">في الانتظار</span>
                        @endif
                    </p>
                    @if($order->transaction->transfer_date)
                        <p class="mb-0">
                            <strong>تاريخ التحويل:</strong> 
                            {{ $order->transaction->transfer_date->format('Y-m-d H:i') }}
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Ship Modal -->
<div class="modal fade" id="shipModal" tabindex="-1">
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
@endsection
