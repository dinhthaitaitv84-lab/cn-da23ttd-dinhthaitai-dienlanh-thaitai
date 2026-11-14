document.addEventListener('DOMContentLoaded', function () {
    var theme = window.getComputedStyle(document.documentElement);
    var primary = theme.getPropertyValue('--primary').trim();
    var muted = theme.getPropertyValue('--muted').trim();
    var border = theme.getPropertyValue('--border').trim();
    var barColor = theme.getPropertyValue('--chart-bar').trim();
    var colors = [
        primary,
        theme.getPropertyValue('--chart-2').trim(),
        theme.getPropertyValue('--chart-3').trim(),
        theme.getPropertyValue('--chart-4').trim(),
        theme.getPropertyValue('--chart-5').trim(),
        theme.getPropertyValue('--chart-6').trim()
    ];
    var formatNumber = new Intl.NumberFormat('vi-VN');

    function fillRoundedTop(context, x, y, width, height, radius) {
        var top = Math.min(radius, Math.max(0, height));
        context.beginPath();
        context.moveTo(x, y + height);
        context.lineTo(x, y + top);
        context.quadraticCurveTo(x, y, x + top, y);
        context.lineTo(x + width - top, y);
        context.quadraticCurveTo(x + width, y, x + width, y + top);
        context.lineTo(x + width, y + height);
        context.closePath();
        context.fill();
    }

    function readSeries(canvas) {
        try {
            var data = JSON.parse(canvas.dataset.series || '[]');
            return Array.isArray(data) ? data : [];
        } catch (error) {
            return [];
        }
    }

    function setupCanvas(canvas) {
        var rect = canvas.getBoundingClientRect();
        if (rect.width < 1) {
            return null;
        }
        var ratio = Math.max(1, window.devicePixelRatio || 1);
        var height = Math.max(240, canvas.dataset.chart === 'pie' ? 320 : 300);
        canvas.width = Math.round(rect.width * ratio);
        canvas.height = Math.round(height * ratio);
        canvas.style.height = height + 'px';
        var context = canvas.getContext('2d');
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.clearRect(0, 0, rect.width, height);
        context.font = '12px system-ui, Segoe UI, sans-serif';
        return { context: context, width: rect.width, height: height };
    }

    function drawBar(canvas, series, grouped) {
        var surface = setupCanvas(canvas);
        if (!surface) {
            return;
        }
        var context = surface.context;
        var width = surface.width;
        var height = surface.height;
        var margin = { top: 18, right: 18, bottom: 58, left: 62 };
        var chartWidth = Math.max(20, width - margin.left - margin.right);
        var chartHeight = Math.max(20, height - margin.top - margin.bottom);
        var maxValue = 0;
        series.forEach(function (item) {
            maxValue = Math.max(maxValue, Number(item.value) || 0);
            if (grouped) {
                maxValue = Math.max(maxValue, Number(item.assigned) || 0, Number(item.completed) || 0);
            }
        });
        maxValue = maxValue || 1;
        var scaleMax = Math.ceil(maxValue / 4) * 4 || 1;

        context.strokeStyle = border;
        context.fillStyle = muted;
        context.textAlign = 'right';
        context.textBaseline = 'middle';
        for (var tick = 0; tick <= 4; tick++) {
            var y = margin.top + chartHeight - (chartHeight * tick / 4);
            var value = scaleMax * tick / 4;
            context.beginPath();
            context.moveTo(margin.left, y);
            context.lineTo(width - margin.right, y);
            context.stroke();
            context.fillText(formatNumber.format(value), margin.left - 8, y);
        }
        var slot = chartWidth / Math.max(1, series.length);
        var barWidth = Math.max(2, Math.min(42, slot * (grouped ? 0.27 : 0.62)));
        var largestValue = -1;
        var largestIndex = -1;
        if (!grouped) {
            series.forEach(function (item, index) {
                var amount = Number(item.value) || 0;
                if (amount > largestValue) {
                    largestValue = amount;
                    largestIndex = index;
                }
            });
        }
        series.forEach(function (item, index) {
            var center = margin.left + slot * (index + 0.5);
            if (grouped) {
                [
                    { value: Number(item.assigned) || 0, color: colors[0], offset: -barWidth / 2 },
                    { value: Number(item.completed) || 0, color: colors[1], offset: barWidth / 2 }
                ].forEach(function (bar) {
                    var barHeight = chartHeight * bar.value / scaleMax;
                    context.fillStyle = bar.color;
                    fillRoundedTop(context, center + bar.offset - barWidth / 2, margin.top + chartHeight - barHeight, barWidth, barHeight, 4);
                });
            } else {
                var amount = Number(item.value) || 0;
                var amountHeight = chartHeight * amount / scaleMax;
                context.fillStyle = index === largestIndex ? primary : barColor;
                fillRoundedTop(context, center - barWidth / 2, margin.top + chartHeight - amountHeight, barWidth, amountHeight, 4);
            }
            context.save();
            context.translate(center, margin.top + chartHeight + 10);
            context.rotate(-Math.PI / 4);
            context.fillStyle = muted;
            context.textAlign = 'right';
            context.textBaseline = 'middle';
            var label = String(item.label || '');
            context.fillText(label.length > 22 ? label.slice(0, 20) + '…' : label, 0, 0);
            context.restore();
        });
        if (grouped) {
            context.textAlign = 'left';
            context.textBaseline = 'middle';
            context.fillStyle = colors[0];
            context.fillRect(margin.left, height - 19, 10, 10);
            context.fillStyle = muted;
            context.fillText('Được giao', margin.left + 15, height - 14);
            context.fillStyle = colors[1];
            context.fillRect(margin.left + 100, height - 19, 10, 10);
            context.fillStyle = muted;
            context.fillText('Hoàn thành', margin.left + 115, height - 14);
        }
    }

    function drawPie(canvas, series) {
        var surface = setupCanvas(canvas);
        if (!surface) {
            return;
        }
        var context = surface.context;
        var width = surface.width;
        var height = surface.height;
        var total = series.reduce(function (sum, item) {
            return sum + Math.max(0, Number(item.value) || 0);
        }, 0);
        var centerX = Math.min(width * 0.36, 155);
        var centerY = height / 2;
        var radius = Math.max(20, Math.min(110, height * 0.34, width * 0.28));
        var angle = -Math.PI / 2;
        series.forEach(function (item, index) {
            var value = Math.max(0, Number(item.value) || 0);
            if (!total || !value) {
                return;
            }
            var nextAngle = angle + Math.PI * 2 * value / total;
            context.beginPath();
            context.moveTo(centerX, centerY);
            context.arc(centerX, centerY, radius, angle, nextAngle);
            context.closePath();
            context.fillStyle = colors[index % colors.length];
            context.fill();
            angle = nextAngle;
        });
        var legendX = centerX + radius + 24;
        var legendY = 38;
        context.textAlign = 'left';
        context.textBaseline = 'middle';
        series.forEach(function (item, index) {
            var y = legendY + index * 24;
            if (y > height - 12) {
                return;
            }
            context.fillStyle = colors[index % colors.length];
            context.fillRect(legendX, y - 6, 12, 12);
            context.fillStyle = muted;
            var label = String(item.label || '');
            var amount = Number(item.value) || 0;
            context.fillText(label.slice(0, 24) + ' (' + formatNumber.format(amount) + ' ₫)', legendX + 18, y);
        });
        if (!total) {
            context.fillStyle = muted;
            context.textAlign = 'center';
            context.fillText('Chưa có doanh thu trong khoảng này', centerX, centerY);
        }
    }

    function drawAll() {
        document.querySelectorAll('canvas.report-chart').forEach(function (canvas) {
            var series = readSeries(canvas);
            if (canvas.dataset.chart === 'pie') {
                drawPie(canvas, series);
            } else {
                drawBar(canvas, series, canvas.dataset.chart === 'grouped-bar');
            }
        });
    }

    document.querySelectorAll('[data-report-quick]').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = button.closest('form');
            var fromInput = form && form.querySelector('[name="from"]');
            var toInput = form && form.querySelector('[name="to"]');
            if (!fromInput || !toInput) {
                return;
            }
            var today = new Date();
            var start = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            if (button.dataset.reportQuick === '7days') {
                start.setDate(start.getDate() - 6);
            } else if (button.dataset.reportQuick === 'month') {
                start = new Date(today.getFullYear(), today.getMonth(), 1);
            } else if (button.dataset.reportQuick === 'year') {
                start = new Date(today.getFullYear(), 0, 1);
            }
            var dateValue = function (date) {
                return date.getFullYear() + '-' +
                    String(date.getMonth() + 1).padStart(2, '0') + '-' +
                    String(date.getDate()).padStart(2, '0');
            };
            fromInput.value = dateValue(start);
            toInput.value = dateValue(today);
            form.requestSubmit();
        });
    });

    document.querySelectorAll('[data-print-report]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });

    drawAll();
    var resizeFrame = 0;
    var scheduleRedraw = function () {
        window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(drawAll);
    };
    window.addEventListener('resize', scheduleRedraw);
    if ('ResizeObserver' in window) {
        var chartObserver = new ResizeObserver(scheduleRedraw);
        document.querySelectorAll('canvas.report-chart').forEach(function (canvas) {
            if (canvas.parentElement) {
                chartObserver.observe(canvas.parentElement);
            }
        });
    }
});
