import pymysql

conn = pymysql.connect(
    host='127.0.0.1',
    port=33060,
    user='simaju',
    password='simaju123',
    db='simaju',
    cursorclass=pymysql.cursors.DictCursor
)
with conn.cursor() as cursor:
    cursor.execute("SELECT id, nama, tipe FROM tbl_customers LIMIT 10")
    print("Customers:", cursor.fetchall())
    cursor.execute("SELECT id, nama_barang, harga_jual, stok FROM products LIMIT 10")
    print("Products:", cursor.fetchall())
