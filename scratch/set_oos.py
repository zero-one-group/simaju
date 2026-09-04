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
    cursor.execute("UPDATE products SET stok = 0 WHERE id = 10")
    conn.commit()
    cursor.execute("SELECT id, nama_barang, harga_jual, stok FROM products WHERE id = 10")
    print("Out of stock product:", cursor.fetchone())
