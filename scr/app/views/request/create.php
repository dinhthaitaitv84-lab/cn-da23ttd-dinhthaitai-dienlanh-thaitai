<div class="request-create-layout">
    <section class="card request-form-card">
        <div class="page-heading">
            <h1>Gửi yêu cầu dịch vụ</h1>
            <p>Chọn dịch vụ, mô tả thiết bị và thời gian bạn mong muốn.</p>
        </div>
        <form method="post" action="/DoAn_DienLanh/scr/request/store" enctype="multipart/form-data" id="request-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <section class="request-form-section">
                <h2>1. Dịch vụ và thiết bị</h2>
                <div class="request-field-grid">
                    <div class="form-group">
                        <label for="category_id">Nhóm dịch vụ</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Chọn nhóm dịch vụ</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= $selectedCategoryId === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="service_id">Dịch vụ</label>
                        <select id="service_id" name="service_id" required data-selected="<?= (int) $selectedServiceId ?>">
                            <option value="">Chọn dịch vụ</option>
                            <?php foreach ($categories as $category): ?>
                                <optgroup label="<?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php foreach ($services as $service): ?>
                                        <?php if ((int) $service['category_id'] !== (int) $category['id']) { continue; } ?>
                                        <option value="<?= (int) $service['id'] ?>" data-category="<?= (int) $service['category_id'] ?>" <?= $selectedServiceId === (int) $service['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <fieldset class="device-fieldset">
                    <legend>Thiết bị</legend>
                    <div class="form-group">
                        <label for="device_id">Thiết bị đã lưu</label>
                        <select id="device_id" name="device_id">
                            <option value="">Nhập thiết bị mới</option>
                            <?php foreach ($devices as $device): ?>
                                <option value="<?= (int) $device['id'] ?>" <?= (int) $deviceId === (int) $device['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($device['name'] . (($device['brand'] ?? '') !== '' ? ' - ' . $device['brand'] : '') . (($device['model'] ?? '') !== '' ? ' ' . $device['model'] : ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="new-device-fields" class="request-field-grid">
                        <div class="form-group">
                            <label for="device_name">Tên thiết bị mới</label>
                            <input id="device_name" name="device_name" maxlength="150" value="<?= htmlspecialchars($deviceName, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-group">
                            <label for="device_brand">Hãng</label>
                            <input id="device_brand" name="device_brand" maxlength="100" value="<?= htmlspecialchars($deviceBrand, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-group request-field-full">
                            <label for="device_model">Model</label>
                            <input id="device_model" name="device_model" maxlength="100" value="<?= htmlspecialchars($deviceModel, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </fieldset>
            </section>

            <section class="request-form-section">
                <h2>2. Tình trạng và lịch hẹn</h2>
                <div class="form-group">
                    <label for="title">Tiêu đề</label>
                    <input id="title" name="title" maxlength="200" required value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="description">Mô tả tình trạng</label>
                    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="request-field-grid">
                    <div class="form-group">
                        <label for="address">Địa chỉ sửa chữa</label>
                        <textarea id="address" name="address" rows="3" maxlength="500" required placeholder="Số nhà, đường, phường/xã, huyện/thành phố, tỉnh" data-service-area-keywords="<?= htmlspecialchars(implode('|', SERVICE_AREA_KEYWORDS), ENT_QUOTES, 'UTF-8') ?>" data-service-area-text="<?= htmlspecialchars(SERVICE_AREA_TEXT, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
                        <small>Địa chỉ cần thuộc <?= htmlspecialchars(str_replace(' và ', ' hoặc ', SERVICE_AREA_TEXT), ENT_QUOTES, 'UTF-8') ?> (ghi rõ tên tỉnh).</small>
                    </div>
                    <div class="form-group">
                        <label for="preferred_time">Thời gian mong muốn</label>
                        <input id="preferred_time" name="preferred_time" type="datetime-local" required value="<?= htmlspecialchars($preferredTime, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="form-group request-image-upload">
                    <span class="form-label">Ảnh tình trạng</span>
                    <div class="request-upload-panel">
                        <label class="request-upload-button" for="images">Chọn ảnh</label>
                        <input class="request-file-input" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                        <div id="image-previews" class="image-previews" aria-live="polite">
                            <p class="request-no-images">Chưa có ảnh nào được chọn</p>
                        </div>
                        <small>Tối đa 5 ảnh · JPG/PNG/WEBP · Tối đa 2MB mỗi ảnh</small>
                    </div>
                </div>
            </section>
            <div class="request-submit-row">
                <button class="request-submit-cta" type="submit">Gửi yêu cầu</button>
            </div>
        </form>
    </section>

    <aside class="request-create-aside">
        <h2>Thông tin trước khi gửi</h2>
        <p>Chọn đúng nhóm dịch vụ và mô tả rõ tình trạng thiết bị để chúng tôi hỗ trợ nhanh hơn.</p>
        <p>Hãy nhập địa chỉ thuộc khu vực phục vụ và chọn thời gian thuận tiện để kỹ thuật viên liên hệ.</p>
        <p class="request-support">Hỗ trợ: <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', CONTACT_HOTLINE), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(CONTACT_HOTLINE, ENT_QUOTES, 'UTF-8') ?></a></p>
        <small><?= htmlspecialchars(CONTACT_HOURS, ENT_QUOTES, 'UTF-8') ?></small>
    </aside>
</div>
