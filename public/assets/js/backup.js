(function () {
  'use strict';
  var ce = document.getElementById('ce'), box = document.getElementById('cloudBox'); if (ce && box) ce.addEventListener('change', function () { box.hidden = !ce.checked; });
  var P = { aws: ['Amazon S3', 'https://s3.amazonaws.com', 'us-east-1', 'استبدل المنطقة بمنطقة الحاوية الفعلية (مثل eu-central-1) وعدّل العنوان إلى s3.المنطقة.amazonaws.com.'],
    r2: ['Cloudflare R2', 'https://ACCOUNT_ID.r2.cloudflarestorage.com', 'auto', 'استبدل ACCOUNT_ID بمعرّف حسابك. المنطقة: auto.'],
    b2: ['Backblaze B2', 'https://s3.us-west-004.backblazeb2.com', 'us-west-004', 'العنوان والمنطقة يظهران في صفحة الحاوية في Backblaze.'],
    wasabi: ['Wasabi', 'https://s3.wasabisys.com', 'us-east-1', 'عدّل العنوان حسب منطقة الحاوية (s3.المنطقة.wasabisys.com).'],
    spaces: ['DigitalOcean Spaces', 'https://nyc3.digitaloceanspaces.com', 'nyc3', 'استبدل nyc3 بمنطقتك.'],
    minio: ['MinIO (خادم خاص)', 'https://minio.example.com', 'us-east-1', 'عنوان خادم MinIO الخاص بك (https).'] };
  var sel = document.getElementById('cp'), note = document.getElementById('cpNote'); if (!sel) return;
  Object.keys(P).forEach(function (k) { var o = document.createElement('option'); o.value = k; o.textContent = P[k][0]; sel.appendChild(o); });
  sel.addEventListener('change', function () { var p = P[sel.value]; if (!p) return; document.getElementById('ep').value = p[1]; document.getElementById('rg').value = p[2]; if (note) note.textContent = p[3]; });
})();
