const API_URL = '../api/system-check.php';

const button = document.getElementById('run-checks');
const summary = document.getElementById('summary');
const list = document.getElementById('checks');

function setSummary(text, state) {
  summary.textContent = text;
  summary.className = 'summary summary--' + state;
}

function renderChecks(checks) {
  list.innerHTML = '';

  checks.forEach(function (check) {
    const item = document.createElement('li');
    item.className = 'check';

    const icon = document.createElement('span');
    icon.className = 'check__icon check__icon--' + (check.ok ? 'ok' : 'fail');
    icon.textContent = check.ok ? '✓' : '✕';

    const body = document.createElement('div');

    const name = document.createElement('div');
    name.className = 'check__name';
    name.textContent = check.name;

    const detail = document.createElement('div');
    detail.className = 'check__detail';
    detail.textContent = check.detail;

    body.appendChild(name);
    body.appendChild(detail);
    item.appendChild(icon);
    item.appendChild(body);
    list.appendChild(item);
  });
}

async function runChecks() {
  button.disabled = true;
  setSummary('Se ruleaza verificarile...', 'idle');
  list.innerHTML = '';

  try {
    const response = await fetch(API_URL, { cache: 'no-store' });
    const result = await response.json();

    if (!result.success) {
      setSummary('Eroare API: ' + result.error, 'fail');
      return;
    }

    renderChecks(result.data.checks);

    const failed = result.data.checks.filter(function (c) {
      return !c.ok;
    }).length;

    if (result.data.all_ok) {
      setSummary('Toate verificarile au trecut (' + result.data.checked_at + ')', 'ok');
    } else {
      setSummary(failed + ' verificari au esuat (' + result.data.checked_at + ')', 'fail');
    }
  } catch (error) {
    setSummary('Nu s-a putut contacta API-ul: ' + error.message, 'fail');
  } finally {
    button.disabled = false;
  }
}

button.addEventListener('click', runChecks);
runChecks();
