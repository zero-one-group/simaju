import pytest
import httpx
import pymysql
import os
from bs4 import BeautifulSoup

@pytest.fixture(scope="session")
def db_conn():
    conn = pymysql.connect(
        host=os.environ.get('DB_HOST', '127.0.0.1'),
        port=int(os.environ.get('DB_PORT', 33060)),
        user=os.environ.get('DB_USER', 'simaju'),
        password=os.environ.get('DB_PASSWORD', 'simaju123'),
        db=os.environ.get('DB_NAME', 'simaju'),
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=True
    )
    yield conn
    conn.close()

@pytest.fixture(scope="session")
def base_url():
    return os.environ.get('BASE_URL', 'http://localhost:8080')

@pytest.fixture(scope="session")
def client(base_url):
    c = httpx.Client(base_url=base_url)
    
    # Login
    response = c.get("/login")
    soup = BeautifulSoup(response.text, 'html.parser')
    token = soup.find('input', {'name': '_token'})['value']
    
    resp = c.post("/login", data={
        '_token': token,
        'email': 'admin@majujaya.co.id',
        'password': 'password'
    })
    
    assert resp.status_code == 302, "Failed to login"
    
    yield c
    c.close()

@pytest.fixture
def get_csrf(client):
    def _get_csrf(url):
        resp = client.get(url)
        soup = BeautifulSoup(resp.text, 'html.parser')
        token_input = soup.find('input', {'name': '_token'})
        if token_input:
            return token_input['value']
        return None
    return _get_csrf
