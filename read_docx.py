import zipfile
import re
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

try:
    with zipfile.ZipFile(r'c:\xampp\htdocs\restaurant-project\baocaoduantotnghiep.docx', 'r') as z:
        xml_content = z.read('word/document.xml').decode('utf-8')
        
        # Thay thế thẻ ngắt đoạn bằng xuống dòng
        text = re.sub(r'<w:p[^>]*>', '\n', xml_content)
        # Loại bỏ tất cả các thẻ XML còn lại
        text = re.sub(r'<[^<]+>', '', text)
        
        print(text.strip())
except Exception as e:
    print(f"Error: {e}")
