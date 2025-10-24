<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>团员评议系统</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 图标 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- 自定义样式 -->
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-users me-2"></i>团员评议系统
            </a>
            <div class="navbar-nav ms-auto">
                <button class="btn btn-publish me-2" data-bs-toggle="modal" data-bs-target="#reportModal">
                    <i class="fas fa-plus me-1"></i>发表自述
                </button>
                <button class="btn btn-export" onclick="exportData()">
                    <i class="fas fa-download me-1"></i>导出数据
                </button>
            </div>
        </div>
    </nav>

    <!-- 主要内容区域 -->
    <div class="container mt-4">
        <!-- 加载状态 -->
        <div id="loadingIndicator" class="text-center py-5">
            <div class="loading"></div>
            <p class="mt-3 text-muted">正在加载自述数据...</p>
        </div>

        <!-- 自述列表 -->
        <div id="reportsContainer" class="row" style="display: none;">
            <!-- 自述卡片将在这里动态生成 -->
        </div>

        <!-- 空状态 -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <i class="fas fa-clipboard-list"></i>
            <h3>暂无自述</h3>
            <p>还没有人发表自述，点击上方"发表自述"按钮来发表第一篇自述吧！</p>
        </div>
    </div>

    <!-- 发表/编辑自述模态框 -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalTitle">发表自述</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reportForm">
                        <input type="hidden" id="reportId" name="report_id">
                        
                        <div class="mb-3">
                            <label for="author" class="form-label">您的姓名</label>
                            <input type="text" class="form-control" id="author" name="author" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">自述标题</label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="请输入自述标题" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">自述内容</label>
                            <textarea class="form-control" id="content" name="content" rows="6" placeholder="请输入您的自述内容..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="saveReport()">
                        <span id="saveButtonText">发表自述</span>
                        <span id="saveButtonLoading" class="loading ms-2" style="display: none;"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 评分模态框 -->
    <div class="modal fade" id="ratingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">为自述评分</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="ratingForm">
                        <input type="hidden" id="ratingReportId" name="report_id">
                        
                        <div class="mb-3">
                            <label for="rater" class="form-label">您的姓名</label>
                            <input type="text" class="form-control" id="rater" name="rater" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">评分</label>
                            <div class="star-rating" id="ratingStars">
                                <span class="star" data-rating="1">★</span>
                                <span class="star" data-rating="2">★</span>
                                <span class="star" data-rating="3">★</span>
                                <span class="star" data-rating="4">★</span>
                                <span class="star" data-rating="5">★</span>
                            </div>
                            <input type="hidden" id="rating" name="rating" value="0">
                            <div class="mt-2">
                                <small class="text-muted">点击星星进行评分（1-5分）</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitRating()">
                        <span id="ratingButtonText">提交评分</span>
                        <span id="ratingButtonLoading" class="loading ms-2" style="display: none;"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 全局变量
        let reports = [];
        let currentEditingReport = null;

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            loadReports();
            initializeEventListeners();
        });

        // 初始化事件监听器
        function initializeEventListeners() {
            // 星星评分交互
            document.getElementById('ratingStars').addEventListener('click', function(e) {
                if (e.target.classList.contains('star')) {
                    const rating = parseInt(e.target.dataset.rating);
                    setStarRating(rating);
                }
            });

            // 星星悬停效果
            document.getElementById('ratingStars').addEventListener('mouseover', function(e) {
                if (e.target.classList.contains('star')) {
                    const rating = parseInt(e.target.dataset.rating);
                    highlightStars(rating);
                }
            });

            // 重置星星样式
            document.getElementById('ratingStars').addEventListener('mouseleave', function() {
                const currentRating = parseInt(document.getElementById('rating').value);
                highlightStars(currentRating);
            });
        }

        // 加载自述列表
        async function loadReports() {
            try {
                const response = await fetch('api.php?action=list');
                const result = await response.json();
                
                if (result.success) {
                    reports = result.data;
                    renderReports();
                } else {
                    showAlert('加载失败：' + result.message, 'danger');
                }
            } catch (error) {
                showAlert('网络错误：' + error.message, 'danger');
            } finally {
                document.getElementById('loadingIndicator').style.display = 'none';
            }
        }

        // 渲染自述列表
        function renderReports() {
            const container = document.getElementById('reportsContainer');
            const emptyState = document.getElementById('emptyState');

            if (reports.length === 0) {
                container.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }

            container.style.display = 'block';
            emptyState.style.display = 'none';

            container.innerHTML = reports.map(report => `
                <div class="col-md-6 col-lg-4">
                    <div class="card report-card">
                        <div class="card-header">
                            <h6 class="card-title">${escapeHtml(report.title)}</h6>
                            <p class="card-subtitle">
                                <i class="fas fa-user me-1"></i>${escapeHtml(report.author)}
                                <span class="ms-2">
                                    <i class="fas fa-clock me-1"></i>${formatDate(report.created_at)}
                                </span>
                            </p>
                        </div>
                        <div class="card-body">
                            <div class="report-content" id="content-${report.id}">
                                ${escapeHtml(report.content)}
                            </div>
                            ${report.content.length > 100 ? '<div class="read-more" onclick="toggleContent(\'' + report.id + '\')">展开阅读</div>' : ''}
                            
                            <div class="rating-container">
                                <div class="star-rating">
                                    ${generateStarDisplay(report.average_rating)}
                                </div>
                                <div class="rating-info">
                                    <span class="badge bg-primary">${report.average_rating || 0}分</span>
                                    <span class="badge bg-secondary">${report.rating_count || 0}人评分</span>
                                </div>
                            </div>
                            
                            <div class="card-actions">
                                <button class="btn btn-rate btn-action" onclick="openRatingModal('${report.id}')">
                                    <i class="fas fa-star me-1"></i>评分
                                </button>
                                <button class="btn btn-edit btn-action" onclick="editReport('${report.id}')">
                                    <i class="fas fa-edit me-1"></i>编辑
                                </button>
                                <button class="btn btn-delete btn-action" onclick="deleteReport('${report.id}')">
                                    <i class="fas fa-trash me-1"></i>删除
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // 生成星星显示
        function generateStarDisplay(rating) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            let stars = '';

            for (let i = 1; i <= 5; i++) {
                if (i <= fullStars) {
                    stars += '<span class="star active">★</span>';
                } else if (i === fullStars + 1 && hasHalfStar) {
                    stars += '<span class="star active">★</span>';
                } else {
                    stars += '<span class="star">★</span>';
                }
            }

            return stars;
        }

        // 展开/收起内容
        function toggleContent(reportId) {
            const content = document.getElementById('content-' + reportId);
            const toggle = content.nextElementSibling;

            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                toggle.textContent = '展开阅读';
            } else {
                content.classList.add('expanded');
                toggle.textContent = '收起';
            }
        }

        // 发表/编辑自述
        function saveReport() {
            const form = document.getElementById('reportForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // 验证表单
            if (!data.title.trim() || !data.content.trim() || !data.author.trim()) {
                showAlert('请填写完整信息', 'danger');
                return;
            }

            const isEdit = currentEditingReport !== null;
            const url = 'api.php?action=' + (isEdit ? 'update' : 'create');
            
            if (isEdit) {
                data.report_id = currentEditingReport;
            }

            showLoading('saveButton');

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                hideLoading('saveButton');
                if (result.success) {
                    showAlert(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
                    form.reset();
                    currentEditingReport = null;
                    document.getElementById('reportModalTitle').textContent = '发表自述';
                    loadReports();
                } else {
                    showAlert(result.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading('saveButton');
                showAlert('网络错误：' + error.message, 'danger');
            });
        }

        // 编辑自述
        function editReport(reportId) {
            const report = reports.find(r => r.id === reportId);
            if (!report) return;

            currentEditingReport = reportId;
            document.getElementById('reportModalTitle').textContent = '编辑自述';
            document.getElementById('reportId').value = reportId;
            document.getElementById('author').value = report.author;
            document.getElementById('title').value = report.title;
            document.getElementById('content').value = report.content;

            new bootstrap.Modal(document.getElementById('reportModal')).show();
        }

        // 删除自述
        function deleteReport(reportId) {
            if (!confirm('确定要删除这条自述吗？此操作不可撤销。')) {
                return;
            }

            fetch('api.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ report_id: reportId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert(result.message, 'success');
                    loadReports();
                } else {
                    showAlert(result.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('网络错误：' + error.message, 'danger');
            });
        }

        // 打开评分模态框
        function openRatingModal(reportId) {
            document.getElementById('ratingReportId').value = reportId;
            document.getElementById('rater').value = '';
            document.getElementById('rating').value = '0';
            setStarRating(0);
            new bootstrap.Modal(document.getElementById('ratingModal')).show();
        }

        // 设置星星评分
        function setStarRating(rating) {
            document.getElementById('rating').value = rating;
            highlightStars(rating);
        }

        // 高亮星星
        function highlightStars(rating) {
            const stars = document.querySelectorAll('#ratingStars .star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // 提交评分
        function submitRating() {
            const form = document.getElementById('ratingForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            if (!data.rater.trim() || !data.rating || data.rating === '0') {
                showAlert('请填写评分信息', 'danger');
                return;
            }

            showLoading('ratingButton');

            fetch('api.php?action=rate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                hideLoading('ratingButton');
                if (result.success) {
                    showAlert(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('ratingModal')).hide();
                    form.reset();
                    loadReports();
                } else {
                    showAlert(result.message, 'danger');
                }
            })
            .catch(error => {
                hideLoading('ratingButton');
                showAlert('网络错误：' + error.message, 'danger');
            });
        }

        // 导出数据
        function exportData() {
            window.open('api.php?action=export', '_blank');
        }

        // 显示加载状态
        function showLoading(buttonId) {
            document.getElementById(buttonId + 'Text').style.display = 'none';
            document.getElementById(buttonId + 'Loading').style.display = 'inline-block';
        }

        // 隐藏加载状态
        function hideLoading(buttonId) {
            document.getElementById(buttonId + 'Text').style.display = 'inline';
            document.getElementById(buttonId + 'Loading').style.display = 'none';
        }

        // 显示提示消息
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // 3秒后自动消失
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }

        // 转义HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 格式化日期
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('zh-CN');
        }

        // 重置发表模态框
        document.getElementById('reportModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('reportForm').reset();
            currentEditingReport = null;
            document.getElementById('reportModalTitle').textContent = '发表自述';
        });
    </script>
</body>
</html>
