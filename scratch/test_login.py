import httpx
from bs4 import BeautifulSoup
import os

url = os.environ.get('BASE_URL', 'http://localhost:8080')
client = httpx.Client()
response = client.get(f"{url}/login")
soup = BeautifulSoup(response.text, 'html.parser')
token = soup.find('input', {'name': '_token'})['value']

print("Token:", token)
resp = client.post(f"{url}/login", data={'_token': token, 'email': 'admin@majujaya.co.id', 'password': 'password'})
print(resp.status_code)
print(resp.headers.get('location'))

response = client.get(f"{url}/order/create")
soup = BeautifulSoup(response.text, 'html.parser')
create_token = soup.find('input', {'name': '_token'})['value']
print("Create Token:", create_token)
