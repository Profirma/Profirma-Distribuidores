import concurrent.futures
import http.cookiejar
import http.server
import json
import os
import re
import ssl
import subprocess
import threading
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid

os.environ.update(SESSION_SECURE="0", ENEXT_API_URL="https://localhost:8443/issue",
                  ENEXT_BASIC_USER="test-basic", ENEXT_BASIC_PASSWORD="test-basic-password",
                  ENEXT_SOCIO_USER="test-socio", ENEXT_SOCIO_PASSWORD="test-socio-password",
                  DIST_PRICE_002="10.00", PHP_CLI_SERVER_WORKERS="4")
PREFIX = "require '/work/app/db.php'; require '/work/app/wallet.php'; $input=json_decode(file_get_contents('php://stdin'),true);"

def php(code, data=None, check=True):
    return subprocess.run(["php", "-r", PREFIX + code], input=json.dumps(data), text=True,
                          capture_output=True, check=check)

def value(code):
    return php("echo " + code + ";").stdout

def sql(statement):
    return php("dist_db()->exec($input);", statement)

def balance(user=1):
    return int(value(f"dist_wallet_balance(dist_db(),{user})"))

def submit(data):
    result = php("try { echo json_encode(dist_submit_emission(dist_db(),$input['user']??1,$input)); } "
                 "catch(Throwable $e) {echo json_encode(['error'=>$e->getMessage()]);}", data)
    return json.loads(result.stdout)

def data(name="success", cedula=None, **extra):
    result = dict(perfil_firma="002", nombres=name, apellidos="Prueba", cedula=cedula or str(1700000000 + counter()),
                  codigo_dactilar="A1234B5678", correo="test@example.com", provincia="Pichincha",
                  ciudad="Quito", parroquia="Centro", direccion="Dirección de prueba", celular="0990000000",
                  request_key=uuid.uuid4().hex + uuid.uuid4().hex, quoted_cost="1000", confirmed="1")
    result.update(extra)
    return result

count = 0
def counter():
    global count
    count += 1
    return count

calls = []
calls_lock = threading.Lock()

class ENEXT(http.server.BaseHTTPRequestHandler):
    def log_message(self, *args):
        pass

    def do_POST(self):
        payload = json.loads(self.rfile.read(int(self.headers["Content-Length"])))
        assert self.headers["Authorization"] == "Basic dGVzdC1iYXNpYzp0ZXN0LWJhc2ljLXBhc3N3b3Jk"
        assert payload["usuario"] == "test-socio" and payload["password"] == "test-socio-password"
        assert payload["tipo_envio"] == "EMAIL" and payload["tipo_clave"] == 1
        assert payload["numero_tramite"].startswith("DIST-")
        assert "quoted_cost" not in payload and "request_key" not in payload and "user" not in payload
        with calls_lock:
            calls.append(payload)
        name = payload["nombres"]
        if name == "drop":
            self.close_connection = True
            return
        if name == "slow":
            time.sleep(.3)
        if name == "reject":
            response, status = {"codigo": 0, "mensaje": "Rechazada"}, 422
        elif name == "server-error":
            response, status = {"codigo": 0}, 503
        elif name == "incomplete":
            response, status = {"codigo": 1}, 200
        else:
            response, status = {"codigo": 1, "token_biometria": "private-token", "link_biometria": "https://example.com/private"}, 200
        body = b"not-json" if name == "malformed" else json.dumps(response).encode()
        if name == "prefix":
            body = b"Provider warning\n" + body
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

subprocess.run(["openssl", "req", "-x509", "-newkey", "rsa:2048", "-nodes", "-days", "1",
                "-keyout", "/tmp/key.pem", "-out", "/usr/local/share/ca-certificates/test-enext.crt",
                "-subj", "/CN=localhost", "-addext", "subjectAltName=DNS:localhost,IP:127.0.0.1"],
               check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
subprocess.run(["update-ca-certificates"], check=True, stdout=subprocess.DEVNULL)
provider = http.server.ThreadingHTTPServer(("127.0.0.1", 8443), ENEXT)
tls = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
tls.load_cert_chain("/usr/local/share/ca-certificates/test-enext.crt", "/tmp/key.pem")
provider.socket = tls.wrap_socket(provider.socket, server_side=True)
threading.Thread(target=provider.serve_forever, daemon=True).start()

# Fresh test database only. Preserve the preexisting credit tables and main-site marker.
sql("CREATE SCHEMA pf_distribuidores; CREATE TABLE public.solicitudes(id INTEGER PRIMARY KEY, marker TEXT);"
    "INSERT INTO public.solicitudes VALUES(1,'untouched');"
    "CREATE TABLE pf_distribuidores.users(id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY, name VARCHAR(120),"
    "email VARCHAR(254) UNIQUE, password_hash TEXT, role TEXT DEFAULT 'distribuidor', active BOOLEAN DEFAULT TRUE);"
    "CREATE TABLE pf_distribuidores.login_limits(account_key CHAR(64) PRIMARY KEY, attempts INTEGER DEFAULT 0, window_start TIMESTAMPTZ DEFAULT NOW());")
php("$s=dist_db()->prepare('INSERT INTO pf_distribuidores.users(name,email,password_hash) VALUES(?,?,?)');"
    "$s->execute(['One','one@example.com',password_hash('Test-only-password-2026',PASSWORD_DEFAULT)]);"
    "$s->execute(['Two','two@example.com',password_hash('Test-only-password-2026',PASSWORD_DEFAULT)]);"
    "dist_wallet_initialize(dist_db());")
sql("INSERT INTO pf_distribuidores.recharges(user_id,amount_cents,bank,bank_key,transfer_reference,reference_key,transfer_date,receipt_mime,receipt_data,receipt_sha256,request_key,status,reviewed_at,reviewed_by)"
    "VALUES(1,10000,'Pichincha','pichincha','test','TEST',CURRENT_DATE,'application/pdf',decode('25504446','hex'),repeat('a',64),repeat('a',64),'aprobada',NOW(),'test');"
    "INSERT INTO pf_distribuidores.wallet_movements(user_id,recharge_id,amount_cents,credited_by) VALUES(1,1,10000,'test');")
assert balance() == 10000
assert value("(int)dist_emissions_ready(dist_db())") == "0"
for _ in range(2):
    subprocess.run(["php", "scripts/install_emissions.php"], check=True)
assert balance() == 10000

# Error diagnostics retain only fixed categories and SQLSTATE, never provider messages.
for message, expected in [
    ("SQLSTATE[22001]: test-socio-password private-token test@example.com 0990000000",
     {"error_category": "provider_database", "sqlstate": "22001"}),
    ("SMTP error test@example.com", {"error_category": "provider_notifications"}),
    ("Credenciales invalidas", {"error_category": "provider_authentication"}),
    ("Perfil no habilitado", {"error_category": "provider_validation"}),
    ("Error interno private-token", {"error_category": "unspecified"}),
]:
    diagnostic = json.loads(php("echo json_encode(dist_enext_error_diagnostic($input));",
                               {"mensaje": message}).stdout)
    assert diagnostic == expected
assert json.loads(php("echo json_encode(dist_enext_error_diagnostic($input));",
                      {"mensaje": ["unexpected"]}).stdout) == {"error_category": "unspecified"}

# Validation and readiness fail before provider calls or reservations.
for override in [dict(quoted_cost="1"), dict(confirmed="0"), dict(cedula="123"),
                 dict(correo="bad"), dict(perfil_firma="999"), dict(perfil_firma="005"),
                 dict(request_key="invalid"), dict(nombres=["wrong"]), dict(celular="bad"), dict(user=2)]:
    assert "error" in submit(data(**override))
assert not calls and balance() == 10000
os.environ["ENEXT_BASIC_PASSWORD"] = ""
assert "error" in submit(data()) and not calls
os.environ["ENEXT_BASIC_PASSWORD"] = "test-basic-password"

first = data("incomplete")
result = submit(first)
assert result["status"] == "registrada", result
assert len(calls) == 1 and balance() == 9000
assert submit(first)["numero_tramite"] == result["numero_tramite"]
assert len(calls) == 1 and balance() == 9000
assert "error" in submit(dict(first, request_key=uuid.uuid4().hex * 2))
assert len(calls) == 1

rejected = data("reject")
assert submit(rejected)["status"] == "rechazada"
assert balance() == 9000
assert submit(rejected)["status"] == "rechazada" and len(calls) == 2

# Unknown results reserve the cost and never resend with the same request key.
for name in ["malformed", "server-error", "drop"]:
    attempt = data(name)
    before = balance()
    row = submit(attempt)
    assert row["status"] == "revision", row
    assert balance() == before - 1000
    n = len(calls)
    assert submit(attempt)["status"] == "revision" and len(calls) == n
    subprocess.run(["php", "scripts/resolver_emision.php", row["numero_tramite"], "rechazada", "Verified in mock provider"], check=True)
    assert balance() == before
    assert submit(attempt)["status"] == "rechazada" and len(calls) == n
assert submit(data("prefix"))["status"] == "registrada" and balance() == 8000

# Parallel repeats: one provider call and one debit, even across PHP processes.
attempt = data("slow")
n = len(calls)
with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
    rows = list(pool.map(submit, [attempt] * 5))
assert len({row["id"] for row in rows}) == 1 and len(calls) == n + 1
assert balance() == 7000

# Parallel distinct requests can spend only the available credit.
os.environ["DIST_PRICE_002"] = "70.00"
with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
    rows = list(pool.map(submit, [data("slow", quoted_cost="7000"), data("slow", quoted_cost="7000")]))
assert sum("error" not in row for row in rows) == 1 and balance() == 0, rows
assert "error" in submit(data(quoted_cost="7000"))
os.environ["DIST_PRICE_002"] = "10.00"

# An approval after issuance remains additive without editing the recharge ledger.
sql("INSERT INTO pf_distribuidores.recharges(user_id,amount_cents,bank,bank_key,transfer_reference,reference_key,transfer_date,receipt_mime,receipt_data,receipt_sha256,request_key)"
    "VALUES(1,5000,'Pichincha','pichincha','test2','TEST2',CURRENT_DATE,'application/pdf',decode('25504446','hex'),repeat('b',64),repeat('b',64));")
php("dist_review_recharge(dist_db(),2,'aprobar','test');")
assert balance() == 5000

# If provider succeeds but the DB update fails, the original reservation survives.
sql("CREATE FUNCTION pf_distribuidores.fail_update() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'test rollback'; END $$;"
    "CREATE TRIGGER test_failure BEFORE UPDATE ON pf_distribuidores.emissions FOR EACH ROW EXECUTE FUNCTION pf_distribuidores.fail_update();")
attempt = data()
n = len(calls)
assert "error" in submit(attempt) and balance() == 4000 and len(calls) == n + 1
sql("DROP TRIGGER test_failure ON pf_distribuidores.emissions; DROP FUNCTION pf_distribuidores.fail_update();")
row = submit(attempt)
assert row["status"] == "enviando" and len(calls) == n + 1
subprocess.run(["php", "scripts/resolver_emision.php", row["numero_tramite"], "registrada", "Accepted by mock provider"], check=True)
assert balance() == 4000
assert value("dist_db()->query('SELECT SUM(amount_cents) FROM pf_distribuidores.wallet_movements')->fetchColumn()") == "15000"
assert value("dist_db()->query('SELECT marker FROM public.solicitudes WHERE id=1')->fetchColumn()") == "untouched"
assert value("dist_db()->query('SELECT COUNT(*) FROM pf_distribuidores.users')->fetchColumn()") == "2"

# Browser flow: auth, CSRF, own history, form prices, balance and recharge history.
def browser():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

base = "http://127.0.0.1:8022/"
def request(client, path, fields=None):
    payload = None if fields is None else urllib.parse.urlencode(fields).encode()
    try:
        response = client.open(base + path, payload, timeout=20)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.read(), response.geturl().split("#", 1)[0]

def csrf(page):
    return re.search(rb'name="csrf" value="([a-f0-9]+)"', page).group(1).decode()

server = subprocess.Popen(["php", "-S", "127.0.0.1:8022", "-t", "/work"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, start_new_session=True)
try:
    for _ in range(100):
        try:
            request(browser(), "index.php")
            break
        except urllib.error.URLError:
            time.sleep(.1)
    assert request(browser(), "emisiones.php")[2].endswith("index.php")
    client = browser()
    page = request(client, "index.php")[1]
    request(client, "login.php", dict(csrf=csrf(page), usuario="one@example.com", password="Test-only-password-2026"))
    page = request(client, "emisiones.php")[1]
    assert b'data-cost="1000"' in page and b"private-token" not in page
    assert request(client, "emisiones.php", data())[0] == 403
    form = data("success", csrf=csrf(page))
    form["request_key"] = re.search(rb'name="request_key" value="([a-f0-9]+)"', page).group(1).decode()
    status, page, url = request(client, "emisiones.php", form)
    assert status == 200 and "tramite=DIST-" in url and b"Registrada en ENEXT" in page
    assert balance() == 3000
    assert b"private-token" not in page and b"/private" not in page
    assert b"Solicitud de firma registrada" in request(client, "recargas.php")[1]
    assert b"$30.00" in request(client, "dashboard.php")[1]
    assert request(client, "scripts/install_emissions.php")[0] == 404
    assert request(client, "scripts/resolver_emision.php")[0] == 404
    other = browser()
    page = request(other, "index.php")[1]
    request(other, "login.php", dict(csrf=csrf(page), usuario="two@example.com", password="Test-only-password-2026"))
    page = request(other, "emisiones.php?tramite=" + row["numero_tramite"])[1]
    assert row["numero_tramite"].encode() not in page and b"Test-only" not in page
    # Taking another user's request key does not retrieve their issuance or spend their credit.
    n = len(calls)
    assert "error" in submit(dict(form, user=2)) and len(calls) == n
finally:
    import signal
    os.killpg(server.pid, signal.SIGTERM)
    server.wait()
    provider.shutdown()
print("PASS: ENEXT HTTPS/auth/payload, wallet reservations and refunds, idempotency, races, DB rollback, auth/CSRF/ownership, browser flow and existing data preserved.")
