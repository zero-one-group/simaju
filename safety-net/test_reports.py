import pytest
from bs4 import BeautifulSoup

def test_report_summary_and_bug(client, db_conn, get_csrf):
    token = get_csrf("/order/create")
    
    # Create an order on a specific test date
    payload = {
        "_token": token,
        "customer_id": "5",
        "tgl_order": "2024-01-01",
        "diskon_persen": "10",
        "product_id[]": "3",
        "qty[]": "1",
        "harga[]": "1000000"
    }
    
    client.post("/order", data=payload)
    
    with db_conn.cursor() as cur:
        # Get the ID of the order we just created
        cur.execute("SELECT id, no_order, tgl_order FROM tbl_orders ORDER BY id DESC LIMIT 1")
        order = cur.fetchone()
        
    # Fetch report
    resp = client.get("/laporan?dari=2024-01-01&sampai=2024-01-01")
    assert resp.status_code == 200
    
    soup = BeautifulSoup(resp.text, 'html.parser')
    
    # Check the specific order row in the "Rincian Transaksi" table
    # We look for the row containing our no_order
    order_row = None
    for tr in soup.find_all('tr'):
        if order['no_order'] in tr.text:
            order_row = tr
            break
            
    assert order_row is not None
    tds = order_row.find_all('td')
    
    # LaporanController calculates total differently from OrderController
    # Order: Sub 1,000,000, Diskon 10% (100,000), PPN 100,000 -> Total 1,000,000
    # Laporan: Sub 1,000,000, Diskon 100,000, DPP 900,000, PPN 90,000 -> Total Hitung 990,000
    # Selisih = 10,000
    
    total_db = tds[10].text.strip().replace('.', '')
    selisih = tds[11].text.strip().replace('.', '')
    
    assert total_db == "1000000"
    assert selisih == "10000" # KNOWN-DEVIATION

def test_report_timezone_bug(client, db_conn, get_csrf):
    token = get_csrf("/order/create")
    
    # Insert order at 18:00
    # Order form doesn't let us set time directly (it uses current time),
    # so we update the DB to 18:00
    
    payload = {
        "_token": token,
        "customer_id": "5",
        "tgl_order": "2024-02-01", # Form only takes date
        "diskon_persen": "0",
        "product_id[]": "3",
        "qty[]": "1",
        "harga[]": "100000"
    }
    
    client.post("/order", data=payload)
    
    with db_conn.cursor() as cur:
        cur.execute("SELECT id FROM tbl_orders ORDER BY id DESC LIMIT 1")
        order_id = cur.fetchone()["id"]
        # Force the time to 18:00
        cur.execute("UPDATE tbl_orders SET tgl_order = '2024-02-01 18:00:00' WHERE id = %s", (order_id,))
        
    # Fetch report for the whole month
    resp = client.get("/laporan?dari=2024-02-01&sampai=2024-02-28")
    soup = BeautifulSoup(resp.text, 'html.parser')
    
    # Find the "Per Hari" table which groups by DATE(DATE_ADD(tgl_order, INTERVAL 7 HOUR))
    # It should put this order on 2024-02-02!
    
    per_hari_div = soup.find(lambda tag: tag.name == 'div' and tag.get('class') == ['panel-heading'] and 'Per Hari' in tag.text)
    per_hari_table = per_hari_div.find_next('table')
    rows = per_hari_table.find('tbody').find_all('tr')
    
    found_02 = False
    for tr in rows:
        tds = tr.find_all('td')
        if '02 ' in tds[0].text and 'Feb' in tds[0].text:
            found_02 = True
            
    assert found_02 is True # KNOWN-DEVIATION
