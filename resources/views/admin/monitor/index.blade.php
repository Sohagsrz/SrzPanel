@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- CPU Usage -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">CPU Usage</h3>
                </div>
                <div class="card-body">
                    <canvas id="cpuChart"></canvas>
                </div>
            </div>
        </div>

        <!-- RAM Usage -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">RAM Usage</h3>
                </div>
                <div class="card-body">
                    <canvas id="ramChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Disk Usage -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Disk Usage</h3>
                </div>
                <div class="card-body">
                    <canvas id="diskChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Bandwidth Usage -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bandwidth Usage</h3>
                </div>
                <div class="card-body">
                    <canvas id="bandwidthChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.card {
    margin-bottom: 20px;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    const cpuChart = new Chart(document.getElementById('cpuChart'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'CPU Load',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    const ramChart = new Chart(document.getElementById('ramChart'), {
        type: 'doughnut',
        data: {
            labels: ['Used', 'Free'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['rgb(255, 99, 132)', 'rgb(75, 192, 192)']
            }]
        },
        options: {
            responsive: true
        }
    });

    const diskChart = new Chart(document.getElementById('diskChart'), {
        type: 'doughnut',
        data: {
            labels: ['Used', 'Free'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['rgb(255, 99, 132)', 'rgb(75, 192, 192)']
            }]
        },
        options: {
            responsive: true
        }
    });

    const bandwidthChart = new Chart(document.getElementById('bandwidthChart'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'RX',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'TX',
                data: [],
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Update charts every 5 seconds
    function updateCharts() {
        fetch('{{ route("admin.monitor.stats") }}')
            .then(response => response.json())
            .then(data => {
                // Update CPU chart
                const now = new Date().toLocaleTimeString();
                cpuChart.data.labels.push(now);
                cpuChart.data.datasets[0].data.push(data.cpu.load1);
                if (cpuChart.data.labels.length > 20) {
                    cpuChart.data.labels.shift();
                    cpuChart.data.datasets[0].data.shift();
                }
                cpuChart.update();

                // Update RAM chart
                const ramUsed = (data.ram.used / data.ram.total) * 100;
                const ramFree = 100 - ramUsed;
                ramChart.data.datasets[0].data = [ramUsed, ramFree];
                ramChart.update();

                // Update Disk chart
                const diskUsed = (data.disk.used / data.disk.total) * 100;
                const diskFree = 100 - diskUsed;
                diskChart.data.datasets[0].data = [diskUsed, diskFree];
                diskChart.update();

                // Update Bandwidth chart
                bandwidthChart.data.labels.push(now);
                bandwidthChart.data.datasets[0].data.push(data.bandwidth.rx);
                bandwidthChart.data.datasets[1].data.push(data.bandwidth.tx);
                if (bandwidthChart.data.labels.length > 20) {
                    bandwidthChart.data.labels.shift();
                    bandwidthChart.data.datasets[0].data.shift();
                    bandwidthChart.data.datasets[1].data.shift();
                }
                bandwidthChart.update();
            });
    }

    // Initial update
    updateCharts();

    // Update every 5 seconds
    setInterval(updateCharts, 5000);
});
</script>
@endpush
@endsection 