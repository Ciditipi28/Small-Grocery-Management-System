"""Checks the running local preview. Reads generated credentials; never prints them."""
from pathlib import Path
from urllib.request import build_opener, HTTPCookieProcessor, Request
from urllib.error import HTTPError
from urllib.parse import urlencode
import http.cookiejar
import re

base = 'http://127.0.0.1:8085/index.php'
access = dict(line.split(': ', 1) for line in (Path(__file__).parents[1] / 'var/preview-access.txt').read_text().splitlines() if ': ' in line)
opener = build_opener(HTTPCookieProcessor(http.cookiejar.CookieJar()))

def get(page):
    with opener.open(base + '?' + page) as response:
        return response.read().decode()

def token(html):
    return re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)

login = get('page=products')
assert 'Sign in to your store' in login
try:
    opener.open(Request(base + '?page=login', urlencode({'action': 'login', 'username': access['Username'], 'password': access['Password'], 'csrf': 'invalid'}).encode()))
except HTTPError:
    pass
assert 'Sign in to your store' in get('page=dashboard')
with opener.open(Request(base + '?page=login', urlencode({'action': 'login', 'username': access['Username'], 'password': access['Password'], 'csrf': token(login)}).encode())) as response:
    assert 'Overview' in response.read().decode()
pages = ['dashboard', 'products', 'product_categories', 'units', 'suppliers', 'stock', 'movements', 'reports', 'users', 'activity']
pages += ['history&type=' + t for t in ['purchase', 'sale', 'return', 'adjustment']]
pages += ['new&type=' + t for t in ['purchase', 'sale', 'return', 'adjustment']]
pages += ['detail&type=' + t + '&id=1' for t in ['purchase', 'sale', 'return', 'adjustment']]
pages += ['new&type=return&sale_id=1', 'movements&product_id=1']
for page in pages:
    html = get('page=' + page)
    assert 'Unable to load this page' not in html, page
    assert 'Sign in to your store' not in html, page
    print('PASS authenticated page:', page)
html = get('page=users&q=smoke_cashier')
match = re.search(r'edit=(\d+)',html)
with opener.open(Request(base + '?page=users', urlencode({'action':'save_master', 'csrf':token(html), 'id':match.group(1) if match else '0', 'username':'smoke_cashier', 'full_name':'Smoke Test Cashier', 'password':'CashierTest!2026', 'role_id':'2','is_active':'1'}).encode())) as response:
    body=response.read().decode()
    assert 'Record saved.' in body or 'already exists' in body
html = get('page=dashboard')
with opener.open(Request(base, urlencode({'action':'logout','csrf':token(html)}).encode())) as response:
    login=response.read().decode()
with opener.open(Request(base, urlencode({'action':'login','username':'smoke_cashier','password':'CashierTest!2026','csrf':token(login)}).encode())) as response:
    assert 'Overview' in response.read().decode()
for page in ['users','products','reports','activity','new&type=purchase','history&type=return']:
    try:
        get('page='+page)
        raise AssertionError('Cashier accessed owner page '+page)
    except HTTPError as error:
        assert error.code == 403
        print('PASS cashier denied:',page)
assert 'New Sale / Checkout' in get('page=new&type=sale')
html = get('page=dashboard')
with opener.open(Request(base, urlencode({'action':'logout','csrf':token(html)}).encode())) as response:
    login=response.read().decode()
with opener.open(Request(base, urlencode({'action':'login','username':access['Username'],'password':access['Password'],'csrf':token(login)}).encode())) as response:
    response.read()
html=get('page=users&q=smoke_cashier')
test_id=re.search(r'edit=(\d+)',html).group(1)
with opener.open(Request(base+'?page=users',urlencode({'action':'save_master','csrf':token(html),'id':test_id,'username':'smoke_cashier','full_name':'Smoke Test Cashier','password':'','role_id':'2'}).encode())) as response:
    assert 'Record saved.' in response.read().decode()
print('PASS temporary cashier account deactivated after testing')
print('All HTTP smoke checks passed.')
