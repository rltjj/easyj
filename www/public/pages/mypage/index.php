<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';
$activeMenu = 'mypage';

$isAdmin = ($_SESSION['role'] === 'ADMIN');

//계정정보
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
  SELECT email, name, phone, role
  FROM users
  WHERE id = :id
");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  die('사용자 정보 없음');
}

//결제정보
$siteId = $currentSiteId;

$stmt = $pdo->prepare("
  SELECT
    service_start,
    service_end,
    is_enabled,
    max_contract_count,
    used_contract_count
  FROM site_service
  WHERE site_id = :site_id
  LIMIT 1
");
$stmt->execute(['site_id' => $siteId]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

$remainingCount = null;
$statusText = '알 수 없음';

if ($service) {
  $remainingCount = $service['max_contract_count'] - $service['used_contract_count'];

  if (!$service['is_enabled']) {
    $statusText = '서비스 비활성화';
  } else {
    $today = date('Y-m-d');

    if ($today < $service['service_start']) {
      $statusText = '서비스 시작 전';
    } elseif ($today > $service['service_end']) {
      $statusText = '서비스 만료';
    } else {
      $statusText = '이용 중';
    }
  }
}

//팀관리
$stmt = $pdo->prepare("
  SELECT
    u.id,
    u.email,
    u.name,
    u.phone,
    u.role,
    ss.joined_at
  FROM users u
  LEFT JOIN site_staff ss
    ON ss.user_id = u.id
    AND ss.site_id = :site_id
  WHERE
    u.id = (
      SELECT operator_id FROM sites WHERE id = :site_id
    )
    OR ss.site_id = :site_id
  ORDER BY
    u.role ASC, ss.joined_at ASC
");
$stmt->execute(['site_id' => $currentSiteId]);
$teamMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 현장정보
$stmt = $pdo->prepare("
  SELECT
    s.name AS site_name,

    oc.company_name,
    oc.company_phone,
    oc.office_address,
    oc.modelhouse_address,

    oa.agency_name,
    oa.manager_phone
  FROM sites s
  LEFT JOIN operator_company oc ON oc.site_id = s.id
  LEFT JOIN operator_agency oa ON oa.site_id = s.id
  WHERE s.id = :site_id
  LIMIT 1
");
$stmt->execute(['site_id' => $currentSiteId]);
$siteInfo = $stmt->fetch(PDO::FETCH_ASSOC);

//직인 관리
$isOperator = ($_SESSION['role'] === 'OPERATOR');

$sql = "
  SELECT id, file_path, is_hided
  FROM stamp
  WHERE site_id = :site_id
";

if (!$isOperator) {
  $sql .= " AND is_hided = 0";
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['site_id' => $currentSiteId]);
$stamps = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>마이페이지</title>
  <link rel="stylesheet" href="../../css/layout.css">
  <link rel="stylesheet" href="../../css/mypage.css">
</head>
<body>
  <div class="sidebar">
    <?php require_once PUBLIC_PATH . '/layouts/sidebar.php'; ?>
  </div>

  <div class="main-area">
    <div class="header">
      <?php require_once PUBLIC_PATH . '/layouts/header.php'; ?>
    </div>

    <main class="content">
      <?php
      $role = $_SESSION['role'];
      ?>

      <div class="mypage-tabs">
        <button class="tab-btn active" data-tab="account">계정정보</button>

        <?php if (in_array($role, ['ADMIN', 'OPERATOR'])): ?>
          <button class="tab-btn" data-tab="billing">결제정보</button>
          <button class="tab-btn" data-tab="team">팀관리</button>
        <?php endif; ?>
        <button class="tab-btn" data-tab="site">현장정보</button>
        <?php if (in_array($role, ['ADMIN', 'OPERATOR'])): ?>
          <button class="tab-btn" data-tab="stamp">직인관리</button>
        <?php endif; ?>
      </div>

      <div class="mypage-tab-content">

        <section class="tab-panel active" id="tab-account">
          <h3>계정정보</h3>
          <div class="account-info">

          <div class="info-row">
            <span class="label">이메일</span>
            <span class="value"><?= htmlspecialchars($user['email']) ?></span>
          </div>

          <div class="info-row">
            <span class="label">이름</span>
            <span class="value"><?= htmlspecialchars($user['name']) ?></span>
          </div>

          <div class="info-row">
            <span class="label">전화번호</span>
            <span class="value"><?= htmlspecialchars($user['phone']) ?></span>
          </div>

          <div class="info-row">
            <span class="label">권한</span>
            <span class="value">
              <?=
                match ($user['role']) {
                  'ADMIN'    => '관리자',
                  'OPERATOR' => '운영자',
                  'STAFF'    => '직원',
                  default    => '-'
                }
              ?>
            </span>
          </div>

        </div>
        </section>

        <section class="tab-panel" id="tab-billing">
          <h3>결제정보</h3>

          <?php if (!$service): ?>
            <p>서비스 정보가 없습니다.</p>
          <?php else: ?>

          <?php if ($isAdmin): ?>
          <form method="post" action="update_service.php">
            <input type="hidden" name="site_id" value="<?= $siteId ?>">
          <?php endif; ?>

          <div class="billing-info">

            <div class="info-row">
              <span class="label">서비스 상태</span>

              <?php if ($isAdmin): ?>
                <select name="is_enabled">
                  <option value="1" <?= $service['is_enabled'] ? 'selected' : '' ?>>활성</option>
                  <option value="0" <?= !$service['is_enabled'] ? 'selected' : '' ?>>비활성</option>
                </select>
              <?php else: ?>
                <span class="value"><?= $statusText ?></span>
              <?php endif; ?>
            </div>

            <div class="info-row">
              <span class="label">서비스 시작일</span>

              <?php if ($isAdmin): ?>
                <input type="date" name="service_start" value="<?= $service['service_start'] ?>">
              <?php else: ?>
                <span class="value"><?= $service['service_start'] ?></span>
              <?php endif; ?>
            </div>

            <div class="info-row">
              <span class="label">서비스 종료일</span>

              <?php if ($isAdmin): ?>
                <input type="date" name="service_end" value="<?= $service['service_end'] ?>">
              <?php else: ?>
                <span class="value"><?= $service['service_end'] ?></span>
              <?php endif; ?>
            </div>

            <div class="info-row">
              <span class="label">총 계약 건수</span>

              <?php if ($isAdmin): ?>
                <input
                  type="number"
                  name="max_contract_count"
                  min="<?= $service['used_contract_count'] ?>"
                  value="<?= $service['max_contract_count'] ?>"
                >
                <span class="hint">
                  (사용 중인 계약 수보다 작게 설정할 수 없습니다)
                </span>
              <?php else: ?>
                <span class="value"><?= number_format($service['max_contract_count']) ?>건</span>
              <?php endif; ?>
            </div>

            <div class="info-row">
              <span class="label">사용한 계약</span>
              <span class="value"><?= number_format($service['used_contract_count']) ?>건</span>
            </div>

            <div class="info-row">
              <span class="label">잔여 계약</span>
              <span class="value"><?= number_format($remainingCount) ?>건</span>
            </div>

          </div>

          <?php if ($isAdmin): ?>
            <div class="form-actions">
              <button type="submit" class="btn-primary">서비스 설정 저장</button>
            </div>
          </form>
          <?php endif; ?>

          <?php endif; ?>
        </section>

        <section class="tab-panel" id="tab-team">
          <h3>팀관리</h3>
          
          <div class="team-register">
            <h4>직원 등록</h4>

            <form method="post" action="register_staff.php">
              <input type="hidden" name="site_id" value="<?= $currentSiteId ?>">

              <div class="form-row">
                <label>이름</label>
                <input type="text" name="name" required>
              </div>

              <div class="form-row">
                <label>이메일</label>
                <input type="email" name="email" required>
              </div>

              <div class="form-row">
                <label>전화번호</label>
                <input type="text" name="phone" required>
              </div>

              <button type="submit" class="btn-primary">직원 등록</button>
            </form>
          </div>

          <hr>

          <div class="team-list">
            <h4>팀 구성원</h4>

            <table class="team-table">
              <thead>
                <tr>
                  <th>이메일</th>
                  <th>이름</th>
                  <th>전화번호</th>
                  <th>역할</th>
                  <th>가입일</th>
                  <th>관리</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($teamMembers as $member): ?>
                  <tr>
                    <td><?= htmlspecialchars($member['email']) ?></td>
                    <td><?= htmlspecialchars($member['name']) ?></td>
                    <td><?= htmlspecialchars($member['phone']) ?></td>
                    <td><?= $member['role'] === 'OPERATOR' ? '운영자' : '직원' ?></td>
                    <td><?= $member['joined_at'] ?? '-' ?></td>
                    <td>
                      <?php if (
                        in_array($_SESSION['role'], ['ADMIN','OPERATOR']) &&
                        $member['role'] === 'STAFF'
                      ): ?>
                        <form method="post"
                              action="remove_staff.php"
                              onsubmit="return confirm('해당 직원을 현장에서 제거하시겠습니까?');"
                              style="display:inline">
                          <input type="hidden" name="site_id" value="<?= $currentSiteId ?>">
                          <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                          <button type="submit" class="btn-danger">제거</button>
                        </form>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="tab-panel" id="tab-site">
          <h3>현장정보</h3>

          <?php if (!$siteInfo): ?>
            <p>현장 정보가 없습니다.</p>
          <?php else: ?>

          <?php if (in_array($_SESSION['role'], ['ADMIN','OPERATOR'])): ?>
            <form method="post" action="update_site_info.php">
              <input type="hidden" name="site_id" value="<?= $currentSiteId ?>">
          <?php endif; ?>

          <div class="site-info">

            <div class="info-row">
              <span class="label">현장명</span>
              <?php if (in_array($_SESSION['role'], ['ADMIN','OPERATOR'])): ?>
                <input type="text" name="site_name" value="<?= htmlspecialchars($siteInfo['site_name']) ?>">
              <?php else: ?>
                <span class="value"><?= htmlspecialchars($siteInfo['site_name']) ?></span>
              <?php endif; ?>
            </div>

            <hr>

            <h4>시행사 정보</h4>

            <div class="info-row">
              <span class="label">시행사 상호명</span>
              <?php if (in_array($_SESSION['role'], ['ADMIN','OPERATOR'])): ?>
                <input type="text" name="company_name" value="<?= htmlspecialchars($siteInfo['company_name']) ?>">
              <?php else: ?>
                <span class="value"><?= htmlspecialchars($siteInfo['company_name']) ?></span>
              <?php endif; ?>
            </div>

            <div class="info-row">
              <span class="label">시행사 대표번호</span>
              <?php if (in_array($_SESSION['role'], ['ADMIN','OPERATOR'])): ?>
                <input type="text" name="company_phone" value="<?= htmlspecialchars($siteInfo['company_phone']) ?>">
              <?php else: ?>
                <span class="value"><?= htmlspecialchars($siteInfo['company_phone']) ?></span>
              <?php endif; ?>
            </div>

            <div class="info-row">
              <span class="label">시행사 주소</span>
              <?php if (in_array($_SESSION['role'], ['ADMIN','OPERATOR'])): ?>
                <input type="text" name="office_address" value="<?= htmlspecialchars($siteInfo['office_address']) ?>">
              <?php else: ?>
                <span class="value"><?= htmlspecialchars($siteInfo['office_address']) ?></span>
              <?php endif; ?>
            </div>

            <div class="info-row">
              <span class="label">모델하우스 주소</span>
              <?php if (in_array($_SESSION['role'], ['ADMIN','OPERATOR'])): ?>
                <input type="text" name="modelhouse_address" value="<?= htmlspecialchars($siteInfo['modelhouse_address']) ?>">
              <?php else: ?>
                <span class="value"><?= htmlspecialchars($siteInfo['modelhouse_address']) ?></span>
              <?php endif; ?>
            </div>

            <hr>

            <h4>모집주체 정보</h4>

            <div class="info-row">
              <span class="label">모집주체 상호명</span>
              <?php if (in_array($_SESSION['role'], ['ADMIN','OPERATOR'])): ?>
                <input type="text" name="agency_name" value="<?= htmlspecialchars($siteInfo['agency_name']) ?>">
              <?php else: ?>
                <span class="value"><?= htmlspecialchars($siteInfo['agency_name']) ?></span>
              <?php endif; ?>
            </div>

            <div class="info-row">
              <span class="label">모집주체 대표번호</span>
              <?php if (in_array($_SESSION['role'], ['ADMIN','OPERATOR'])): ?>
                <input type="text" name="manager_phone" value="<?= htmlspecialchars($siteInfo['manager_phone']) ?>">
              <?php else: ?>
                <span class="value"><?= htmlspecialchars($siteInfo['manager_phone']) ?></span>
              <?php endif; ?>
            </div>

          </div>

          <?php if (in_array($_SESSION['role'], ['ADMIN','OPERATOR'])): ?>
            <div class="form-actions">
              <button type="submit" class="btn-primary">현장정보 저장</button>
            </div>
            </form>
          <?php endif; ?>

          <?php endif; ?>
        </section>

        <section class="tab-panel" id="tab-stamp">
          <h3>직인관리</h3>

          <?php if ($isOperator): ?>
          <form
            class="stamp-upload-form"
            action="stamp_upload.php"
            method="post"
            enctype="multipart/form-data"
          >
            <input type="hidden" name="site_id" value="<?= $currentSiteId ?>">
            <input type="file" name="stamp" accept="image/*" required>
            <button type="submit" class="btn-primary">직인 추가</button>
          </form>
          <?php endif; ?>

          <div class="stamp-grid">
            <?php if (!$stamps): ?>
              <p>등록된 직인이 없습니다.</p>
            <?php endif; ?>

            <?php foreach ($stamps as $stamp): ?>
              <div class="stamp-card <?= $stamp['is_hided'] ? 'hided' : '' ?>">
                <img src="/easyj/<?= htmlspecialchars($stamp['file_path']) ?>">

                <?php if ($isOperator): ?>
                <div class="stamp-actions">
                  <form method="post" action="stamp_toggle.php">
                    <input type="hidden" name="id" value="<?= $stamp['id'] ?>">
                    <button type="submit">
                      <?= $stamp['is_hided'] ? '표시' : '숨김' ?>
                    </button>
                  </form>

                  <form
                    method="post"
                    action="stamp_delete.php"
                    onsubmit="return confirm('직인을 삭제하시겠습니까?');"
                  >
                    <input type="hidden" name="id" value="<?= $stamp['id'] ?>">
                    <button type="submit" class="danger">삭제</button>
                  </form>
                </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

      </div>
    </main>
  </div>
<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const tab = btn.dataset.tab;

    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

    btn.classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
  });
});
</script>
</body>
</html>
