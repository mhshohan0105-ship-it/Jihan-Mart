import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);
Chart.defaults.font.family = "'Inter', 'Hind Siliguri', sans-serif";
Chart.defaults.color = '#64748b';

// Jihan Mart logo palette for series
export const PALETTE = ['#f47c20', '#1b2b57', '#3f8f3a', '#6a3d9a', '#0ea5e9', '#ec4899', '#f59e0b', '#14b8a6', '#ef4444', '#8b5cf6'];

/** <div x-data="chart({ type, labels, data, label, money })"><canvas x-ref="canvas"></canvas></div> */
export function chart({ type = 'bar', labels = [], data = [], label = '', money = true, horizontal = false }) {
    return {
        init() {
            const many = type === 'doughnut' || type === 'pie';
            new Chart(this.$refs.canvas, {
                type,
                data: {
                    labels,
                    datasets: [{
                        label,
                        data,
                        backgroundColor: many ? PALETTE : type === 'line' ? 'rgba(244,124,32,.15)' : '#f47c20',
                        borderColor: many ? '#fff' : '#f47c20',
                        borderWidth: many ? 2 : 2,
                        borderRadius: type === 'bar' ? 6 : 0,
                        fill: type === 'line',
                        tension: 0.35,
                        pointRadius: type === 'line' ? 3 : 0,
                    }],
                },
                options: {
                    indexAxis: horizontal ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: many, position: 'bottom' },
                        tooltip: { callbacks: { label: (c) => (money ? '৳' : '') + Number(c.raw).toLocaleString('en-US') } },
                    },
                    scales: many ? {} : {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, ticks: { callback: (v) => (money ? '৳' : '') + Number(v).toLocaleString('en-US') } },
                    },
                },
            });
        },
    };
}
