import socket
import time
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed
import os

class ThreadSafeCounter:
    def __init__(self):
        self.lock = threading.Lock()
        self.count = 0

    def increment(self):
        with self.lock:
            self.count += 1

    def get_count(self):
        with self.lock:
            return self.count

# 定义目标IP地址列表和端口范围
ips = ["108.181.32.169"]
ports = range(10000, 55536)

# 创建UDP套接字
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(1)

def ensure_directory_exists(file_path):
    directory = os.path.dirname(file_path)
    if not os.path.exists(directory):
        try:
            os.makedirs(directory)
            print(f"Directory {directory} created successfully.")
        except PermissionError:
            print(f"Permission denied: Unable to create directory {directory}.")
            raise

def check_file_writable(file_path):
    try:
        with open(file_path, 'w'):
            pass
        print(f"File {file_path} is writable.")
    except PermissionError:
        print(f"Permission denied: Unable to write to file {file_path}.")
        raise
    except Exception as e:
        print(f"Error checking file {file_path}: {e}")
        raise

# Thread-safe lock for file writing
file_lock = threading.Lock()

# 定义扫描函数
def p2p(ip, port, counter, file_path, stop_event):
    hex_data = "000000000000000000000700001d4d5441344c6a45344d53347a4d6934784e6a6b364d6a597a4e544d3d00"
    bytes_data = bytes.fromhex(hex_data)

    # 发送数据
    sock.sendto(bytes_data, (ip, port))

    try:
        data, address = sock.recvfrom(1024)
        if address[1] != port and not stop_event.is_set():
            print(f"Preparing to write data to file: p3p://{ip}:{address[1]}")
            with file_lock:  # Use lock to ensure thread-safe writing
                with open(file_path, 'w') as file:
                    file.write(f"p3p://{ip}:{address[1]}\n")
            print(f"Data written to file: p3p://{ip}:{address[1]}")
            counter.increment()
            stop_event.set()  # Set the stop event to indicate a result has been found
    except socket.timeout:
        pass
    except Exception as e:
        print(f"Error with {ip}:{port}: {e}")
        counter.increment()

# 定义打印进度的函数
def print_progress(counter, stop_event):
    try:
        while not stop_event.is_set():
            print(f"\rCompleted: {counter.get_count()}", end='', flush=True)
            time.sleep(0.5)
        print(f"\rCompleted: {counter.get_count()}", end='', flush=True)  # Final print
    except KeyboardInterrupt:
        print("\nProgress printing interrupted.")

# 初始化线程安全计数器和停止事件
counter = ThreadSafeCounter()
stop_event = threading.Event()

# 定义扫描结果存储文件路径
file_path = 'IPTV/169.txt'

# 确保目录存在并检查文件写入权限
ensure_directory_exists(file_path)
check_file_writable(file_path)

# 启动进度打印线程
progress_thread = threading.Thread(target=print_progress, args=(counter, stop_event))
progress_thread.start()

# 定义线程池最大工作线程数
max_pool = 800
pool = ThreadPoolExecutor(max_workers=max_pool)

# 存储所有的 Future 对象
futures = []

# 提交任务到线程池
for ip in ips:
    for port in ports:
        if stop_event.is_set():
            break
        future = pool.submit(p2p, ip, port, counter, file_path, stop_event)
        futures.append(future)
    if stop_event.is_set():
        break

# 等待所有任务完成
for future in as_completed(futures):
    try:
        future.result()
    except Exception as exc:
        print(f"Generated an exception: {exc}")

# 关闭套接字和退出事件
sock.close()
progress_thread.join()  # 确保进度打印线程正常结束
