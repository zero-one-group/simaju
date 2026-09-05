import pytest

def test_order_retail_below_5m(client, db_conn, get_csrf):
    token = get_csrf("/order/create")
    
    payload = {
        "_token": token,
        "customer_id": "5",  # retail
        "tgl_order": "2023-10-01",
        "diskon_persen": "0",
        "product_id[]": "3",
        "qty[]": "1",
        "harga[]": "1000000"  # 1,000,000
    }
    
    resp = client.post("/order", data=payload)
    assert resp.status_code == 302
    
    with db_conn.cursor() as cur:
        cur.execute("SELECT * FROM tbl_orders ORDER BY id DESC LIMIT 1")
        order = cur.fetchone()
        
    assert order["subtotal"] == 1000000
    assert order["diskon_persen"] == 0
    assert order["diskon"] == 0
    assert order["ppn"] == 100000
    assert order["total"] == 1100000

def test_order_retail_exactly_5m(client, db_conn, get_csrf):
    token = get_csrf("/order/create")
    
    payload = {
        "_token": token,
        "customer_id": "5",  # retail
        "tgl_order": "2023-10-02",
        "diskon_persen": "0",
        "product_id[]": "1",
        "qty[]": "1",
        "harga[]": "5000000"
    }
    
    resp = client.post("/order", data=payload)
    assert resp.status_code == 302
    
    with db_conn.cursor() as cur:
        cur.execute("SELECT * FROM tbl_orders ORDER BY id DESC LIMIT 1")
        order = cur.fetchone()
        
    assert order["subtotal"] == 5000000
    assert order["diskon_persen"] == 2
    assert order["diskon"] == 100000
    assert order["ppn"] == 500000
    assert order["total"] == 5400000

def test_order_retail_exactly_20m(client, db_conn, get_csrf):
    token = get_csrf("/order/create")
    
    payload = {
        "_token": token,
        "customer_id": "5",  # retail
        "tgl_order": "2023-10-03",
        "diskon_persen": "0",
        "product_id[]": "1",
        "qty[]": "1",
        "harga[]": "20000000"
    }
    
    resp = client.post("/order", data=payload)
    assert resp.status_code == 302
    
    with db_conn.cursor() as cur:
        cur.execute("SELECT * FROM tbl_orders ORDER BY id DESC LIMIT 1")
        order = cur.fetchone()
        
    assert order["subtotal"] == 20000000
    assert order["diskon_persen"] == 5
    assert order["diskon"] == 1000000
    assert order["ppn"] == 2000000
    assert order["total"] == 21000000

def test_order_grosir_above_5m(client, db_conn, get_csrf):
    token = get_csrf("/order/create")
    
    payload = {
        "_token": token,
        "customer_id": "1",  # grosir
        "tgl_order": "2023-10-04",
        "diskon_persen": "0",
        "product_id[]": "1",
        "qty[]": "1",
        "harga[]": "6000000"
    }
    
    resp = client.post("/order", data=payload)
    assert resp.status_code == 302
    
    with db_conn.cursor() as cur:
        cur.execute("SELECT * FROM tbl_orders ORDER BY id DESC LIMIT 1")
        order = cur.fetchone()
        
    assert order["subtotal"] == 6000000
    assert order["diskon_persen"] == 5  # 2 (5m) + 3 (grosir)
    assert order["diskon"] == 300000
    assert order["ppn"] == 600000
    assert order["total"] == 6300000

def test_order_manual_diskon_capped_at_30(client, db_conn, get_csrf):
    token = get_csrf("/order/create")
    
    payload = {
        "_token": token,
        "customer_id": "5",
        "tgl_order": "2023-10-05",
        "diskon_persen": "28", # plus 5% from 20M = 33% -> capped at 30%
        "product_id[]": "1",
        "qty[]": "1",
        "harga[]": "20000000"
    }
    
    resp = client.post("/order", data=payload)
    assert resp.status_code == 302
    
    with db_conn.cursor() as cur:
        cur.execute("SELECT * FROM tbl_orders ORDER BY id DESC LIMIT 1")
        order = cur.fetchone()
        
    assert order["subtotal"] == 20000000
    assert order["diskon_persen"] == 30
    assert order["diskon"] == 6000000
    assert order["ppn"] == 2000000
    assert order["total"] == 16000000

def test_order_out_of_stock_admin(client, db_conn, get_csrf):
    token = get_csrf("/order/create")
    
    payload = {
        "_token": token,
        "customer_id": "5",
        "tgl_order": "2023-10-06",
        "diskon_persen": "0",
        "product_id[]": "10", # stok 0
        "qty[]": "1",
        "harga[]": "100000"
    }
    
    resp = client.post("/order", data=payload)
    assert resp.status_code == 302
    
    with db_conn.cursor() as cur:
        cur.execute("SELECT * FROM tbl_orders ORDER BY id DESC LIMIT 1")
        order = cur.fetchone()
        
        cur.execute("SELECT * FROM order_items WHERE order_id = %s", (order["id"],))
        items = cur.fetchall()
        
    assert order["subtotal"] == 100000
    assert order["diskon_persen"] == 0
    assert order["total"] == 110000
    assert len(items) == 1
    assert items[0]["product_id"] == 10
