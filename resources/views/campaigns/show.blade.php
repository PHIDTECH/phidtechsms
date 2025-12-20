@extends('layouts.modern-dashboard')

@section('title', 'Campaign: ' . $campaign->name)

@section('styles')
<style>
.campaign-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.campaign-title {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
}

.campaign-meta {
    font-size: 1rem;
    opacity: 0.9;
    margin-top: 0.5rem;
}

.status-badge {
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    margin-top: 1rem;
}

.status-draft {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}

.status-scheduled {
    background: #fff3cd;
    color: #856404;
}

.status-sending {
    background: #cce5ff;
    color: #0066cc;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-failed {
    background: #f8d7da;
    color: #721c24;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: #2c3e50;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.progress-section {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.progress-title {
    font-size: 1.3rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 1.5rem;
}

.progress-bar-container {
    background: #e9ecef;
    border-radius: 10px;
    height: 20px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    border-radius: 10px;
    transition: width 0.5s ease;
    position: relative;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
}

.message-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}

.message-stat {
    text-align: center;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.message-stat.pending {
    background: #f8f9fa;
    border-color: #dee2e6;
}

.message-stat.sent {
    background: #e3f2fd;
    border-color: #bbdefb;
}

.message-stat.delivered {
    background: #e8f5e8;
    border-color: #c8e6c9;
}

.message-stat.failed {
    background: #ffebee;
    border-color: #ffcdd2;
}

.message-stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
}

.message-stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.campaign-details {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #495057;
}

.detail-value {
    color: #6c757d;
}

.message-preview {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.message-content {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    font-family: 'Courier New', monospace;
    line-height: 1.6;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-action {
    padding: 0.75rem 2rem;
    border-radius: 25px;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary-action {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.btn-primary-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    color: white;
}

.btn-danger-action {
    background: #dc3545;
    color: white;
    border: none;
}

.btn-danger-action:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
    color: white;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -0.5rem;
    top: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: #667eea;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-left: 1rem;
}

.timeline-title {
    font-weight: bold;
    color: #2c3e50;
    margin: 0 0 0.5rem 0;
}

.timeline-time {
    color: #6c757d;
    font-size: 0.8rem;
}

.refresh-notice {
    background: #e3f2fd;
    border: 1px solid #bbdefb;
    color: #1976d2;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: center;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Campaign Header -->
    <div class="campaign-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="campaign-title">{{ $campaign->name }}</h1>
                <div class="campaign-meta">
                    <i class="fas fa-calendar"></i> Created {{ $campaign->created_at->format('M d, Y H:i') }}
                    @if($campaign->schedule_at)
                        | <i class="fas fa-clock"></i> Scheduled for {{ $campaign->schedule_at->format('M d, Y H:i') }}
                    @endif
                    | <i class="fas fa-id-badge"></i> Sender: {{ $campaign->sender_id }}
                </div>
                <span class="status-badge status-{{ $campaign->status }}">
                    {{ ucfirst($campaign->status) }}
                </span>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('campaigns.index') }}" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left"></i> Back to Campaigns
                </a>
            </div>
        </div>
    </div>

    @if(in_array($campaign->status, ['sending', 'scheduled']))
        <div class="refresh-notice">
            <i class="fas fa-sync-alt"></i> This page will automatically refresh every 30 seconds to show the latest progress.
        </div>
    @endif

    @php $rate = (int) config('services.sms.cost_per_part', 30); @endphp
    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon text-primary">
                <i class="fas fa-users"></i>
            </div>
            <p class="stat-number">{{ number_format($campaign->estimated_recipients) }}</p>
            <p class="stat-label">Recipients</p>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon text-info">
                <i class="fas fa-sms"></i>
            </div>
            <p class="stat-number">{{ max(1, ceil(($campaign->estimated_parts ?? 0) / max(1, ($campaign->estimated_recipients ?? 1)))) }}</p>
            <p class="stat-label">SMS Parts per Message</p>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon text-warning">
                <i class="fas fa-calculator"></i>
            </div>
            <p class="stat-number">{{ number_format($campaign->estimated_parts) }}</p>
            <p class="stat-label">Total SMS Parts</p>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon text-success">
                <i class="fas fa-sms"></i>
            </div>
            <p class="stat-number">{{ number_format(floor(($campaign->estimated_cost ?? 0) / max($rate,1))) }} SMS</p>
            <p class="stat-label">Estimated SMS</p>
        </div>
        
        @if($campaign->status === 'completed')
            <div class="stat-card">
                <div class="stat-icon text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <p class="stat-number">{{ $campaign->delivery_rate }}%</p>
                <p class="stat-label">Delivery Rate</p>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Progress Section -->
            @if(in_array($campaign->status, ['sending', 'completed']))
                <div class="progress-section">
                    <h3 class="progress-title">
                        <i class="fas fa-chart-line"></i> Campaign Progress
                    </h3>
                    
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: {{ $campaign->progress }}%">
                                <span class="progress-text">{{ $campaign->progress }}% Complete</span>
                        </div>
                    </div>
                    
                    <div class="message-stats">
                        <div class="message-stat queued">
                            <p class="message-stat-number">{{ $messageStats['queued'] }}</p>
                            <p class="message-stat-label">Queued</p>
                        </div>
                        <div class="message-stat sent">
                            <p class="message-stat-number">{{ $messageStats['sent'] }}</p>
                            <p class="message-stat-label">Sent</p>
                        </div>
                        <div class="message-stat delivered">
                            <p class="message-stat-number">{{ $messageStats['delivered'] }}</p>
                            <p class="message-stat-label">Delivered</p>
                        </div>
                        <div class="message-stat failed">
                            <p class="message-stat-number">{{ $messageStats['failed'] }}</p>
                            <p class="message-stat-label">Failed</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Message Preview -->
            <div class="message-preview">
                <h3><i class="fas fa-eye"></i> Message Preview</h3>
                    <div class="message-content">{{ $campaign->message }}</div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                    Message length: {{ strlen($campaign->message) }} characters |
                    SMS parts per recipient: {{ max(1, ceil(($campaign->estimated_parts ?? 0) / max(1, ($campaign->estimated_recipients ?? 1)))) }}
                    </small>
                </div>
            </div>

            <!-- Campaign Timeline -->
            <div class="campaign-details">
                <h3><i class="fas fa-history"></i> Campaign Timeline</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <h5 class="timeline-title">Campaign Created</h5>
                            <p class="timeline-time">{{ $campaign->created_at->format('M d, Y H:i:s') }}</p>
                        </div>
                    </div>
                    
                @if($campaign->schedule_at && $campaign->status !== 'draft')
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <h5 class="timeline-title">Scheduled</h5>
                    <p class="timeline-time">{{ $campaign->schedule_at->format('M d, Y H:i:s') }}</p>
                            </div>
                        </div>
                    @endif
                    
                    @if($campaign->started_at)
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <h5 class="timeline-title">Sending Started</h5>
                                <p class="timeline-time">{{ $campaign->started_at->format('M d, Y H:i:s') }}</p>
                            </div>
                        </div>
                    @endif
                    
                    @if($campaign->completed_at)
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <h5 class="timeline-title">Campaign Completed</h5>
                                <p class="timeline-time">{{ $campaign->completed_at->format('M d, Y H:i:s') }}</p>
                                <p class="mb-0">
                                    <small class="text-muted">
                                        {{ $campaign->messages_sent ?? 0 }} messages sent, 
                                        {{ $campaign->messages_failed ?? 0 }} failed
                                    </small>
                                </p>
                            </div>
                        </div>
                    @endif
                    
                    @if($campaign->status === 'failed' && $campaign->failure_reason)
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <h5 class="timeline-title text-danger">Campaign Failed</h5>
                                <p class="timeline-time">{{ $campaign->updated_at->format('M d, Y H:i:s') }}</p>
                                <p class="mb-0 text-danger">{{ $campaign->failure_reason }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Campaign Details -->
            <div class="campaign-details">
                <h3><i class="fas fa-info-circle"></i> Campaign Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Campaign ID:</span>
                    <span class="detail-value">#{{ $campaign->id }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge status-{{ $campaign->status }}">
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Sender ID:</span>
                    <span class="detail-value">{{ $campaign->sender_id }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Recipients:</span>
                <span class="detail-value">{{ number_format($campaign->estimated_recipients) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Message Length:</span>
                <span class="detail-value">{{ strlen($campaign->message) }} characters</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">SMS Parts:</span>
                <span class="detail-value">{{ max(1, ceil(($campaign->estimated_parts ?? 0) / max(1, ($campaign->estimated_recipients ?? 1)))) }} per message</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total SMS Parts:</span>
                <span class="detail-value">{{ number_format($campaign->estimated_parts) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Estimated SMS:</span>
                    <span class="detail-value">{{ number_format(floor(($campaign->estimated_cost ?? 0) / max($rate,1))) }} SMS</span>
                </div>
                
                @if($campaign->schedule_at)
                    <div class="detail-row">
                        <span class="detail-label">Scheduled For:</span>
                    <span class="detail-value">{{ $campaign->schedule_at->format('M d, Y H:i') }}</span>
                    </div>
                @endif
                
                @if($campaign->started_at)
                    <div class="detail-row">
                        <span class="detail-label">Started At:</span>
                        <span class="detail-value">{{ $campaign->started_at->format('M d, Y H:i') }}</span>
                    </div>
                @endif
                
                @if($campaign->completed_at)
                    <div class="detail-row">
                        <span class="detail-label">Completed At:</span>
                        <span class="detail-value">{{ $campaign->completed_at->format('M d, Y H:i') }}</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value">
                            @if($campaign->started_at && $campaign->completed_at)
                                {{ $campaign->started_at->diffForHumans($campaign->completed_at, true) }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                @endif
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                @if(in_array($campaign->status, ['draft', 'failed']))
                    <form method="POST" action="{{ route('campaigns.destroy', $campaign->id) }}" 
                          onsubmit="return confirm('Are you sure you want to delete this campaign? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-action btn-danger-action">
                            <i class="fas fa-trash"></i> Delete Campaign
                        </button>
                    </form>
                @endif
                
                @if($campaign->status === 'completed')
                    <a href="#" class="btn-action btn-primary-action">
                        <i class="fas fa-download"></i> Export Report
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const campaignStatus = '{{ $campaign->status }}';
    if (['sending', 'scheduled'].includes(campaignStatus)) {
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    }

    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        setTimeout(function() {
            progressBar.style.width = '{{ $campaign->progress }}%';
        }, 500);
    }
});
</script>
@endsection
