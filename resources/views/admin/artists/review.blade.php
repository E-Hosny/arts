@extends('admin.layout')

@section('title', 'مراجعة الفنان')
@section('page-title', 'مراجعة الفنان: ' . $artist->user->name)

@section('content')
<div class="row">
    <!-- Artist Info -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    بيانات الفنان
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px; font-size: 2rem;">
                        {{ strtoupper(substr($artist->user->name, 0, 1)) }}
                    </div>
                    <h5 class="mt-2 mb-0">{{ $artist->user->name }}</h5>
                    @if($artist->status === 'pending')
                        <span class="badge pending-badge">في الانتظار</span>
                    @elseif($artist->status === 'approved')
                        <span class="badge approved-badge">معتمد</span>
                    @else
                        <span class="badge rejected-badge">مرفوض</span>
                    @endif
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">البريد الإلكتروني</label>
                    <p>{{ $artist->user->email }}</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">رقم الهاتف</label>
                    <p>{{ $artist->phone }}</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">المدينة</label>
                    <p>{{ $artist->city }}</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">تاريخ التقديم</label>
                    <p>{{ $artist->created_at->format('Y/m/d H:i') }}</p>
                    <small class="text-muted">{{ $artist->created_at->diffForHumans() }}</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Artist Bio & Samples -->
    <div class="col-md-8 mb-4">
        <!-- Bio -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-text me-2"></i>
                    السيرة الذاتية
                </h5>
            </div>
            <div class="card-body">
                <p>{{ $artist->bio }}</p>
            </div>
        </div>
        
        <!-- Samples -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-images me-2"></i>
                    نماذج الأعمال ({{ $artist->samples->count() }})
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($artist->samples as $sample)
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <img src="{{ url($sample->image_url) }}" 
                                     class="card-img-top" 
                                     alt="{{ $sample->title }}"
                                     style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $sample->title }}</h6>
                                    @if($sample->description)
                                        <p class="card-text">{{ $sample->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@if($artist->status === 'pending')
<!-- Action Buttons -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-gavel me-2"></i>
                    إجراءات المراجعة
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Approve Form -->
                    <div class="col-md-6">
                        <form id="approveForm" method="POST" action="{{ route('admin.artists.approve', $artist) }}">
                            @csrf
                            <h6 class="text-success">
                                <i class="fas fa-check-circle me-2"></i>
                                قبول الفنان
                            </h6>
                            
                            <div class="mb-3">
                                <label for="commission_rate" class="form-label">معدل العمولة (%)</label>
                                <input type="number" class="form-control" id="commission_rate" 
                                       name="commission_rate" value="25" min="5" max="50">
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1">
                                    <label class="form-check-label" for="featured">
                                        إبراز الفنان (Featured)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="approve_notes" class="form-label">ملاحظات (اختياري)</label>
                                <textarea class="form-control" id="approve_notes" name="notes_admin" rows="3"
                                          placeholder="ملاحظات للأدمن..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-2"></i>
                                قبول الفنان
                            </button>
                        </form>
                    </div>
                    
                    <!-- Reject Form -->
                    <div class="col-md-6">
                        <form id="rejectForm" method="POST" action="{{ route('admin.artists.reject', $artist) }}">
                            @csrf
                            <h6 class="text-danger">
                                <i class="fas fa-times-circle me-2"></i>
                                رفض الفنان
                            </h6>
                            
                            <div class="mb-3">
                                <label for="reason_rejection" class="form-label">سبب الرفض *</label>
                                <textarea class="form-control" id="reason_rejection" name="reason_rejection" 
                                          rows="4" required
                                          placeholder="نشكرك على اهتمامك. نرى أن الأعمال المقدمة تحتاج إلى مزيد من التطوير..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reject_notes" class="form-label">ملاحظات للأدمن (اختياري)</label>
                                <textarea class="form-control" id="reject_notes" name="notes_admin" rows="3"
                                          placeholder="ملاحظات داخلية للأدمن..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-times me-2"></i>
                                رفض الفنان
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Approve Form - Add confirmation
    document.getElementById('approveForm').addEventListener('submit', function(e) {
        if (!confirm('هل أنت متأكد من قبول هذا الفنان؟')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Reject Form - Add confirmation
    document.getElementById('rejectForm').addEventListener('submit', function(e) {
        if (!confirm('هل أنت متأكد من رفض هذا الفنان؟')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush
@endsection
