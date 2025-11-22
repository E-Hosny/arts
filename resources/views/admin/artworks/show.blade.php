@extends('admin.layout')

@section('title', 'تفاصيل العمل الفني')
@section('page-title', 'تفاصيل العمل الفني')

@section('content')
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-image me-2"></i>
                    {{ $artwork->title }}
                </h5>
            </div>
            <div class="card-body">
                <!-- Images Gallery -->
                @if($artwork->images && count($artwork->images) > 0)
                    <div class="mb-4">
                        <div class="row">
                            @foreach($artwork->images as $image)
                                <div class="col-md-6 mb-3">
                                    <img src="{{ url($image) }}" alt="{{ $artwork->title }}" 
                                         class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <h6>الوصف</h6>
                    <p>{{ $artwork->description }}</p>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>السعر:</strong> 
                        <span class="text-primary fs-5">{{ number_format($artwork->price) }} ريال</span>
                    </div>
                    <div class="col-md-6">
                        <strong>الفئة:</strong> 
                        <span class="badge bg-secondary">{{ $artwork->category }}</span>
                    </div>
                </div>

                @if($artwork->dimensions)
                    <div class="mb-3">
                        <strong>الأبعاد:</strong> {{ $artwork->dimensions }}
                    </div>
                @endif

                @if($artwork->materials)
                    <div class="mb-3">
                        <strong>المواد:</strong> {{ $artwork->materials }}
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>المشاهدات:</strong> {{ number_format($artwork->views) }}
                    </div>
                    <div class="col-md-6">
                        <strong>الإعجابات:</strong> {{ number_format($artwork->likes) }}
                    </div>
                </div>

                <div class="mb-3">
                    <strong>الحالة:</strong>
                    @if($artwork->status === 'available')
                        <span class="badge approved-badge">متاح</span>
                    @elseif($artwork->status === 'sold')
                        <span class="badge" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">مُباع</span>
                    @else
                        <span class="badge pending-badge">في الانتظار</span>
                    @endif
                </div>

                <div class="mb-3">
                    <strong>تاريخ الإنشاء:</strong> {{ $artwork->created_at->format('Y-m-d H:i') }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <!-- Artist Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    معلومات الفنان
                </h6>
            </div>
            <div class="card-body">
                <h6>{{ $artwork->artist->user->name }}</h6>
                <p class="text-muted mb-2">
                    <i class="fas fa-envelope me-1"></i>
                    {{ $artwork->artist->user->email }}
                </p>
                <p class="text-muted mb-2">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    {{ $artwork->artist->city ?? 'غير محدد' }}
                </p>
                <p class="text-muted mb-2">
                    <i class="fas fa-phone me-1"></i>
                    {{ $artwork->artist->phone ?? 'غير محدد' }}
                </p>
                <p class="mb-2">
                    <strong>معدل العمولة:</strong> {{ $artwork->artist->commission_rate }}%
                </p>
                <p class="mb-0">
                    <strong>الحالة:</strong>
                    @if($artwork->artist->status === 'approved')
                        <span class="badge approved-badge">معتمد</span>
                    @elseif($artwork->artist->status === 'pending')
                        <span class="badge pending-badge">في الانتظار</span>
                    @else
                        <span class="badge rejected-badge">مرفوض</span>
                    @endif
                </p>
                <div class="mt-3">
                    <a href="{{ route('admin.artists.review', $artwork->artist) }}" 
                       class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-eye me-1"></i>
                        عرض ملف الفنان
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Update -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-edit me-2"></i>
                    تحديث الحالة
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.artworks.update-status', $artwork) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">الحالة الجديدة</label>
                        <select name="status" class="form-select" required>
                            <option value="available" {{ $artwork->status === 'available' ? 'selected' : '' }}>متاح</option>
                            <option value="pending" {{ $artwork->status === 'pending' ? 'selected' : '' }}>في الانتظار</option>
                            <option value="sold" {{ $artwork->status === 'sold' ? 'selected' : '' }}>مُباع</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i>
                        حفظ التغييرات
                    </button>
                </form>
            </div>
        </div>

        <!-- Orders Count -->
        @if($artwork->orders->count() > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>
                        الطلبات
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <strong>عدد الطلبات:</strong> {{ $artwork->orders->count() }}
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-12">
        <a href="{{ route('admin.artworks.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right me-1"></i>
            العودة للقائمة
        </a>
    </div>
</div>
@endsection
