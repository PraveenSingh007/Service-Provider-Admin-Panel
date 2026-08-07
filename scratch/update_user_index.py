import os

fpath = 'html/src/user/index.php'
with open(fpath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add Service Metadata Array after $services = $serviceRepo->findAll();
metadata_code = '''$services = $serviceRepo->findAll();

$serviceMetadata = [
    1 => [
        'badge' => '24/7 Security & Surveillance',
        'carousel_title' => 'Smart CCTV Installation & HD Monitoring',
        'carousel_desc' => 'Protect your home, office, and enterprise with crystal-clear IP cameras, night vision, DVR/NVR configuration, and instant mobile live view setup.',
        'desc' => 'Complete 1080p/4K HD & IP CCTV surveillance system setup, DVR/NVR configuration, night vision camera installation, cable routing, and remote mobile viewing integration.',
        'icon' => 'bx-camcorder',
        'category' => 'cctv_camera'
    ],
    2 => [
        'badge' => 'Expert IT & Hardware Support',
        'carousel_title' => 'Computer Repair, Upgrades & IT Support',
        'carousel_desc' => 'Fast doorstep repair for desktops, laptops, servers, and networking hardware by certified field engineers.',
        'desc' => 'Expert doorstep computer & laptop repairs, SSD & RAM speed upgrades, custom PC assembly, motherboard chip-level troubleshooting, data recovery, and IT hardware sales.',
        'icon' => 'bx-laptop',
        'category' => 'computer_hardware'
    ],
    3 => [
        'badge' => 'Hassle-Free Protection',
        'carousel_title' => 'Annual Maintenance Contracts (AMC)',
        'carousel_desc' => 'Zero downtime with proactive quarterly checkups, priority technician dispatch, and zero service charges.',
        'desc' => 'Hassle-free quarterly preventive maintenance, zero labor charges, 24/7 priority technician response, free emergency calls, and extended hardware lifespan for businesses.',
        'icon' => 'bx-shield-alt-2',
        'category' => 'amc_contract'
    ],
    9 => [
        'badge' => 'Cooling & Climate Control',
        'carousel_title' => 'Air Conditioner Installation & Jet Service',
        'carousel_desc' => 'Beat the heat with certified AC installation, deep chemical jet foam washing, gas top-up, and PCB repairs.',
        'desc' => 'Split & window AC installation, uninstallation, deep chemical foam jet cleaning, R32/R410a refrigerant gas top-up, compressor repair, and PCB troubleshooting.',
        'icon' => 'bx-wind',
        'category' => 'air_conditioner'
    ],
    10 => [
        'badge' => 'Smart Access & Time Attendance',
        'carousel_title' => 'Biometric Installation & Attendance Systems',
        'carousel_desc' => 'Secure your premises with facial recognition, fingerprint readers, and cloud payroll time tracking.',
        'desc' => 'Advanced biometric fingerprint, face recognition, and RFID card access control terminal installation synced with cloud attendance & payroll management software.',
        'icon' => 'bx-fingerprint',
        'category' => 'biometric'
    ],
    11 => [
        'badge' => 'Enterprise Equipment Care',
        'carousel_title' => 'Commercial Laundry Equipment Maintenance',
        'carousel_desc' => 'Industrial washer & dryer maintenance, electrical panel repairs, and motor servicing for commercial setups.',
        'desc' => 'Heavy-duty commercial washer & dryer installation, motor & belt replacement, electrical panel troubleshooting, and scheduled preventive care for hotels & laundromats.',
        'icon' => 'bx-closet',
        'category' => 'commercial_laundry'
    ]
];'''

if '$serviceMetadata' not in content:
    content = content.replace('$services = $serviceRepo->findAll();', metadata_code)

# 2. Add Brand Logo CSS
brand_css = '''    .brand-logo-card {
      background: #ffffff;
      border: 1px solid rgba(161, 172, 184, 0.25);
      border-radius: 1.1rem;
      padding: 0.75rem 1.25rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      min-width: 180px;
      height: 90px;
      flex-shrink: 0;
    }

    .brand-logo-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 28px rgba(105, 108, 255, 0.25);
      border-color: #696cff;
    }

    .brand-logo-img {
      max-height: 65px;
      max-width: 150px;
      width: auto;
      height: auto;
      object-fit: contain;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.06));
    }'''

if '.brand-logo-card' not in content:
    content = content.replace('/* Floating Callback Button */', brand_css + '\n\n    /* Floating Callback Button */')

# 3. Dynamic Carousel Block for ALL services
carousel_code = '''  <!-- Services Image Carousel at Top -->
  <div class="hero-carousel-container">
    <div id="topServicesCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4500">
      
      <!-- Slide Indicators -->
      <div class="carousel-indicators mb-4">
        <?php foreach ($services as $index => $srv): ?>
          <button type="button" data-bs-target="#topServicesCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>"></button>
        <?php endforeach; ?>
      </div>

      <div class="carousel-inner">
        <?php foreach ($services as $index => $srv): ?>
          <?php
          $srvId = (int)$srv->getId();
          $srvName = $srv->getServiceName();
          $srvImg = $srv->getServiceImage();
          
          $meta = $serviceMetadata[$srvId] ?? [
              'badge' => 'Professional Service',
              'carousel_title' => $srvName,
              'carousel_desc' => 'Certified technician visit, maintenance, installation, and doorstep repairs tailored to your needs.',
              'desc' => 'Comprehensive technician visit, inspection, diagnostics, and doorstep repairs for ' . $srvName . '.',
              'icon' => 'bx-wrench',
              'category' => 'other'
          ];

          $cleanPath = ltrim((string)$srvImg, '/');
          if (strpos($cleanPath, 'html/') === 0) {
              $cleanPath = substr($cleanPath, 5);
          }
          $bgImgSrc = '/sneat/html/' . ($cleanPath ?: 'uploads/services/cctv_service.png');
          ?>
          <div class="carousel-item <?= $index === 0 ? 'active' : '' ?> hero-slide-item" style="background-image: url('<?= htmlspecialchars($bgImgSrc, ENT_QUOTES, 'UTF-8') ?>');">
            <div class="hero-overlay">
              <div class="container">
                <div class="row align-items-center">
                  <div class="col-lg-8 text-white">
                    <span class="slide-badge mb-3"><i class="bx <?= htmlspecialchars($meta['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i> <?= htmlspecialchars($meta['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                    <h1 class="display-4 fw-extrabold mb-3 text-white"><?= htmlspecialchars($meta['carousel_title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="lead mb-4 text-light opacity-90"><?= htmlspecialchars($meta['carousel_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="d-flex flex-wrap gap-3">
                      <a href="book-service.php?category=<?= urlencode($meta['category']) ?>&service_id=<?= $srvId ?>" class="btn btn-primary btn-lg fw-bold rounded-pill px-4 shadow"><i class="bx bx-calendar-check me-2"></i> Book <?= htmlspecialchars($srvName, ENT_QUOTES, 'UTF-8') ?></a>
                      <button type="button" class="btn btn-outline-light btn-lg fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" data-service="<?= htmlspecialchars($meta['category'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bx bx-phone-call me-2"></i> Request Call Back
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Controls -->
      <button class="carousel-control-prev" type="button" data-bs-target="#topServicesCarousel" data-bs-slide="prev">
        <span class="carousel-control-btn"><i class="bx bx-chevron-left"></i></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#topServicesCarousel" data-bs-slide="next">
        <span class="carousel-control-btn"><i class="bx bx-chevron-right"></i></span>
      </button>
    </div>
  </div>'''

pos1 = content.find('<!-- Services Image Carousel at Top -->')
pos2 = content.find('<!-- Main Content Container -->')
if pos1 != -1 and pos2 != -1:
    content = content[:pos1] + carousel_code + '\n\n  ' + content[pos2:]

# 4. Services Offered Grid Block (with full descriptions for each service)
services_grid_code = '''      <div class="row g-4">
        <?php if (count($services) === 0): ?>
          <div class="col-12 text-center text-muted py-5">No service offerings available at the moment.</div>
        <?php else: ?>
          <?php foreach ($services as $srv): ?>
            <?php
            $srvId = (int)$srv->getId();
            $srvName = $srv->getServiceName();
            $srvImg = $srv->getServiceImage();
            
            $meta = $serviceMetadata[$srvId] ?? [
                'badge' => 'Professional Service',
                'desc' => 'Comprehensive technician visit, inspection, diagnostics, and doorstep repairs for ' . $srvName . '.',
                'category' => 'other'
            ];

            $cleanPath = ltrim((string)$srvImg, '/');
            if (strpos($cleanPath, 'html/') === 0) {
                $cleanPath = substr($cleanPath, 5);
            }
            $imgSrc = '/sneat/html/' . ($cleanPath ?: 'uploads/services/cctv_service.png');
            ?>
            <div class="col-lg-4 col-md-6">
              <div class="card service-card">
                <div class="service-img-container">
                  <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" class="service-card-img" alt="<?= htmlspecialchars($srvName, ENT_QUOTES, 'UTF-8') ?>" />
                  <span class="category-pill"><i class="bx bx-check-circle me-1 text-primary"></i> <?= htmlspecialchars($meta['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="card-body service-card-body">
                  <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($srvName, ENT_QUOTES, 'UTF-8') ?></h5>
                  <p class="text-muted fs-7 mb-4 flex-grow-1"><?= htmlspecialchars($meta['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                  
                  <div class="mt-auto d-flex gap-2 pt-2">
                    <a href="book-service.php?category=<?= urlencode($meta['category']) ?>&service_id=<?= $srvId ?>" class="btn btn-primary fw-bold flex-grow-1 py-2 shadow-sm">
                      <i class="bx bx-calendar-plus me-1"></i> Book Now
                    </a>
                    <button type="button" class="btn btn-outline-primary fw-semibold px-3 py-2" data-bs-toggle="modal" data-bs-target="#requestCallbackModal" data-service="<?= htmlspecialchars($meta['category'], ENT_QUOTES, 'UTF-8') ?>" title="Quick Call Back">
                      <i class="bx bx-phone-call"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>'''

pos3 = content.find('<div class="row g-4">', content.find('Our Offered Services'))
pos4 = content.find('<!-- Brands Logo Automatic Scroll Section', pos3)
if pos3 != -1 and pos4 != -1:
    content = content[:pos3] + services_grid_code + '\n\n    ' + content[pos4:]

# 5. Top Brands We Install & Support Section (With uploaded brand logos)
brands_block_code = '''    <!-- Brands Logo Automatic Scroll Section -->
    <div class="brands-section-wrapper mb-5">
      <div class="text-center mb-4">
        <span class="badge bg-label-primary px-3 py-2 rounded-pill fs-7 fw-bold text-uppercase mb-2">Industry Partners & Brands</span>
        <h3 class="fw-extrabold text-dark mb-1">Top Brands We Install & Support</h3>
        <p class="text-muted">We work directly with leading security, IT hardware, HVAC, and commercial equipment manufacturers.</p>
      </div>

      <div class="marquee-container">
        <div class="marquee-track">
          <?php for ($loop = 0; $loop < 2; $loop++): ?>
            <?php for ($b = 1; $b <= 9; $b++): ?>
              <div class="brand-logo-card">
                <img src="/sneat/assets/img/brands/brand_<?= $b ?>.jpg" alt="Supported Brand Logo <?= $b ?>" class="brand-logo-img" />
              </div>
            <?php endfor; ?>
          <?php endfor; ?>
        </div>
      </div>
    </div>'''

pos5 = content.find('<!-- Brands Logo Automatic Scroll Section')
pos6 = content.find('<!-- Custom Call Back Banner -->')
if pos5 != -1 and pos6 != -1:
    content = content[:pos5] + brands_block_code + '\n\n    ' + content[pos6:]

with open(fpath, 'w', encoding='utf-8') as f:
    f.write(content)

print('Updated index.php successfully!')
