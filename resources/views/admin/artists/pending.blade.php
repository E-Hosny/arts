@extends('admin.layout')

@section('title', 'مراجعة الفنانين')
@section('page-title', 'الفنانين في الانتظار')

@section('content')
<div class="row">
    <div class="col-12">
        @if($artists->count() > 0)
            <div class="row">
                @foreach($artists as $artist)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">{{ $artist->user->name }}</h6>
                                <span class="badge pending-badge">في الانتظار</span>
                            </div>
                            
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">البريد الإلكتروني:</small><br>
                                    <strong>{{ $artist->user->email }}</strong>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">الهاتف:</small><br>
                                    <strong>{{ $artist->phone }}</strong>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">المدينة:</small><br>
                                    <strong>{{ $artist->city }}</strong>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">السيرة الذاتية:</small><br>
                                    <p class="mb-0">{{ Str::limit($artist->bio, 100) }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">عدد النماذج:</small>
                                    <span class="badge bg-info text-dark">{{ $artist->samples->count() }}</span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">تاريخ التقديم:</small><br>
                                    <small>{{ $artist->created_at->format('Y/m/d H:i') }}</small>
                                </div>
                            </div>
                            
                            <div class="card-footer bg-transparent">
                                <div class="d-grid">
                                    <a href="{{ route('admin.artists.review', $artist) }}" 
                                       class="btn btn-primary">
                                        <i class="fas fa-eye me-2"></i>
                                        مراجعة مفصلة
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $artists->links() }}
            </div>
        @else
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5>ممتاز! لا توجد طلبات في الانتظار</h5>
                    <p class="text-muted">جميع طلبات الفنانين تم مراجعتها</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
