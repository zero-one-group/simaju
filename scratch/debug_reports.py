import httpx
import pymysql
import os
from bs4 import BeautifulSoup

client = httpx.Client(base_url="http://localhost:8080")
r = client.get("/login")
soup = BeautifulSoup(r.text, 'html.parser')
token = soup.find('input', {'name': '_token'})['value']
client.post("/login", data={'_token': token, 'email': 'admin@majujaya.co.id', 'password': 'password'})

r = client.get("/laporan?dari=2024-01-01&sampai=2024-01-01")
soup = BeautifulSoup(r.text, 'html.parser')

print("TABLES:")
for tbl in soup.find_all('table'):
    prev = tbl.find_previous(['div', 'h4', 'h3'])
    print(prev.text.strip() if prev else "No heading")

print("----------------------")
for tr in soup.find_all('tr'):
    if 'MJ/' in tr.text:
        tds = [td.text.strip() for td in tr.find_all('td')]
        print(tds)
        break

r2 = client.get("/laporan?dari=2024-02-01&sampai=2024-02-28")
soup2 = BeautifulSoup(r2.text, 'html.parser')
print("----------------------")
for tbl in soup2.find_all('table'):
    prev = tbl.find_previous(['div', 'h4', 'h3'])
    if prev and 'Per Hari' in prev.text:
        for tr in tbl.find_all('tr'):
            tds = [td.text.strip() for td in tr.find_all(['th', 'td'])]
            print(tds)
