import os
import requests
import re
import time
import webbrowser
import socket
import threading
from datetime import datetime
from http.server import HTTPServer, SimpleHTTPRequestHandler

# ============================================
# EVIBE - SIMPLE LOCAL HOST VERSION
# ============================================

print("\n" + "="*50)
print("        🌐 EVIBE VIBE CODER 🌐")
print("="*50)

# Create directory
os.makedirs("evibe_sites", exist_ok=True)

def get_free_port():
    """Get a free port for local hosting"""
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(('', 0))
        s.listen(1)
        port = s.getsockname()[1]
    return port

def start_local_server(port, directory):
    """Start a simple local HTTP server"""
    os.chdir(directory)
    handler = SimpleHTTPRequestHandler
    
    server = HTTPServer(('localhost', port), handler)
    
    print(f"🌐 Server started on http://localhost:{port}")
    
    # Start server in background thread
    server_thread = threading.Thread(target=server.serve_forever)
    server_thread.daemon = True
    server_thread.start()
    
    return server, server_thread

while True:
    # Get website description
    desc = input("\n📝 Website ka description likho  ").strip()
    
    if desc.lower() in ['quit', 'exit', 'q']:
        print("\n👋 Shukriya!")
        break
    
    # Special command for local hosting
    if desc.lower() in ['hello cal hostess server', 'han', 'yes', 'y', 'haan']:
        print("\n🌐 Local server chalu kar raha hoon...")
        
        # Check for existing sites
        sites = os.listdir("evibe_sites")
        if not sites:
            print("❌ Pehle koi website banayein")
            continue
            
        print("\n📁 Available websites:")
        for i, site in enumerate(sites, 1):
            print(f"  {i}. {site}")
        
        try:
            choice = int(input("\n📋 Kaunsi website host karna chahte ho? (number dalo): ")) - 1
            if 0 <= choice < len(sites):
                project_dir = f"evibe_sites/{sites[choice]}"
                
                # Get free port
                port = get_free_port()
                
                # Start local server
                server, server_thread = start_local_server(port, project_dir)
                
                local_url = f"http://localhost:{port}"
                print(f"\n✅ Server ready!")
                print(f"🔗 Local URL: {local_url}")
                print(f"📁 Directory: {project_dir}")
                
                # Open in browser
                open_browser = input("\n🚀 Browser mein kholo? (y/n): ").strip().lower()
                if open_browser in ['y', 'yes', 'haan', 'han']:
                    webbrowser.open(local_url)
                    print("✅ Browser mein khol raha hoon...")
                
                print("\n" + "="*50)
                print("🖥️ SERVER CHALU HAI")
                print("="*50)
                print(f"\n📍 Sirf localhost par available: {local_url}")
                print("⏹️  Server band karne ke liye Ctrl+C dabao")
                
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    print("\n\n🛑 Server band ho gaya")
                    server.shutdown()
                    
            else:
                print("❌ Galat number")
        except ValueError:
            print("❌ Sahi number dalo")
        except Exception as e:
            print(f"❌ Error: {e}")
        
        continue
    
    if not desc:
        print("❌ Kuch to likho")
        continue
    
    print("\n⚡ Website bana raha hoon...")
    
    # AI prompt for HTML code
    ai_prompt = f"""SYSTEM: Tum sirf RAW HTML CODE do. Kuch aur nahi.

RULES (MUST FOLLOW):
1. Sirf HTML code do
2. <!DOCTYPE html> se start karo
3. Sab CSS <style> tags ke andar
4. Sab JavaScript <script> tags ke andar
5. Font Awesome use karo
6. Beautiful, responsive, animated banao
7. Koi explanation nahi, koi comment nahi
8. Complete single file HTML banao

USER: {desc}

OUTPUT (EXACTLY IS FORMAT MEIN):
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Website</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* CSS yahan */
</style>
</head>
<body>
<!-- HTML yahan -->
</body>
<script>
// JavaScript yahan
</script>
</html>"""
    
    try:
        # Step 1: Get code from AI
        print("🤖 AI se code le raha hoon...")
        url = f"https://rajan-perplexitiy-ai.vercel.app/api/ask?prompt={requests.utils.quote(ai_prompt)}"
        
        headers_ai = {
            'User-Agent': 'Mozilla/5.0',
            'Accept': 'application/json'
        }
        
        resp = requests.get(url, headers=headers_ai, timeout=30)
        
        if resp.status_code != 200:
            print(f"❌ AI API error: {resp.status_code}")
            continue
        
        # Get raw HTML code
        try:
            data = resp.json()
            html_code = data.get('answer', '')
        except:
            html_code = resp.text
        
        if not html_code:
            print("❌ Code nahi mila")
            continue
        
        # Step 2: Save file
        timestamp = int(time.time())
        project_name = re.sub(r'[^\w\s-]', '', desc[:20]).strip().replace(' ', '_')
        if not project_name:
            project_name = f"site_{timestamp}"
        
        project_dir = f"evibe_sites/{project_name}"
        os.makedirs(project_dir, exist_ok=True)
        
        filename = "index.html"
        filepath = f"{project_dir}/{filename}"
        
        # Ensure it starts with <!DOCTYPE html>
        if not html_code.strip().startswith('<!DOCTYPE'):
            html_code = f"<!DOCTYPE html>\n{html_code}"
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(html_code)
        
        print(f"✅ File saved: {project_dir}/{filename}")
        
        # Create simple README
        readme_content = f"""# {desc}

Generated on: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}
"""
        
        with open(f"{project_dir}/README.md", 'w', encoding='utf-8') as f:
            f.write(readme_content)
        
        # Save prompt
        with open(f"{project_dir}/prompt.txt", 'w', encoding='utf-8') as f:
            f.write(desc)
        
        # Simple hosting option
        host_now = input("\n🌐 Abhi local server chalaaye? (y/n): ").strip().lower()
        
        if host_now in ['y', 'yes', 'haan', 'han']:
            # Get free port
            port = get_free_port()
            
            # Start local server
            print(f"\n🚀 Local server chalu kar raha hoon...")
            server, server_thread = start_local_server(port, project_dir)
            
            local_url = f"http://localhost:{port}"
            print(f"\n✅ Server ready!")
            print(f"🔗 Local URL: {local_url}")
            
            # Open in browser
            open_browser = input("\n🚀 Browser mein kholo? (y/n): ").strip().lower()
            if open_browser in ['y', 'yes', 'haan', 'han']:
                webbrowser.open(local_url)
                print("✅ Browser mein khol raha hoon...")
            
            print("\n" + "="*50)
            print("🖥️ SERVER CHALU HAI")
            print("="*50)
            print(f"\n📍 Sirf localhost par available: {local_url}")
            print("⏹️  Server band karne ke liye Ctrl+C dabao")
            
            try:
                while True:
                    time.sleep(1)
            except KeyboardInterrupt:
                print("\n\n🛑 Server band ho gaya")
                server.shutdown()
        
        # Direct file open option
        direct_open = input("\n📁 Direct file kholo? (y/n): ").strip().lower()
        if direct_open in ['y', 'yes', 'haan', 'han']:
            abs_path = os.path.abspath(filepath)
            file_url = f"file://{abs_path}"
            print(f"🌐 Opening: {file_url}")
            webbrowser.open(file_url)
        
        # Show summary
        print("\n" + "="*50)
        print("📊 SUMMARY")
        print("="*50)
        print(f"\n📁 Project: {project_name}")
        print(f"📝 Description: {desc}")
        print(f"📅 Created: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"📄 Files: {project_dir}/")
        
    except Exception as e:
        print(f"❌ Error: {e}")

print("\n" + "="*50)
print("👋 EVIBE VIBE CODER - Phir milenge!")
print("="*50)