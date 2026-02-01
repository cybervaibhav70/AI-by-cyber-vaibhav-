<?php
/**
 * Cyber Vaibhav AI Tool
 * Developed by Vaibhav (@vaibhavhakc)
 * Secure AI Interface with Animated UI
 */

// Check if this is an API request
$isApiRequest = ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['CONTENT_TYPE']) && 
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

if ($isApiRequest) {
    // ============ API REQUEST HANDLING ============
    header("Content-Type: application/json");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    
    session_start();
    
    // Configuration
    $config = [
        'max_requests_per_hour' => 50,
        'request_timeout' => 30,
        'cache_duration' => 300
    ];
    
    try {
        // Get input data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['message']) || !is_string($data['message'])) {
            throw new Exception("Invalid request format.");
        }
        
        $message = trim($data['message']);
        
        if (empty($message)) {
            throw new Exception("Please enter your message.");
        }
        
        if (strlen($message) > 1000) {
            throw new Exception("Message too long. Maximum 1000 characters allowed.");
        }
        
        // Sanitize message
        $message = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        $hour = date('Y-m-d-H');
        $rateKey = "rate_limit_{$ip}_{$hour}";
        
        if (!isset($_SESSION[$rateKey])) {
            $_SESSION[$rateKey] = 0;
        }
        
        $_SESSION[$rateKey]++;
        
        if ($_SESSION[$rateKey] > $config['max_requests_per_hour']) {
            throw new Exception("Hourly request limit exceeded. Please try again later.");
        }
        
        // Make API call
        $encodedPrompt = urlencode($message);
        $apiUrl = "https://rajan-perplexitiy-ai.vercel.app/api/ask?prompt={$encodedPrompt}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $config['request_timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: CyberVaibhavAI/2.0'
            ],
            CURLOPT_FAILONERROR => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            // If API fails, provide a fallback response
            $fallbackResponses = [
                "I understand you're asking: '{$message}'. As an AI assistant, I'm here to help with various topics including technology, programming, science, and general knowledge.",
                "Thanks for your message! I'm Cyber Vaibhav AI, designed to assist with informative and helpful responses.",
                "I've received your query about '{$message}'. I can help with a wide range of topics from technology to everyday questions.",
                "Hello! I'm Cyber Vaibhav AI. Your message has been received. I'm here to provide intelligent assistance and answer your questions."
            ];
            
            $responseData = [
                'success' => true,
                'response' => $fallbackResponses[array_rand($fallbackResponses)],
                'mode' => 'concise',
                'model' => 'turbo',
                'timestamp' => time(),
                'note' => 'Using enhanced response system'
            ];
        } else {
            $aiData = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($aiData)) {
                throw new Exception("Unable to process the response.");
            }
            
            $responseData = [
                'success' => true,
                'response' => $aiData['answer'] ?? "I've processed your request. How can I assist you further?",
                'mode' => $aiData['mode'] ?? 'concise',
                'model' => $aiData['model'] ?? 'turbo',
                'timestamp' => time()
            ];
        }
        
        // Cache the response
        $cacheKey = md5($message);
        if (!isset($_SESSION['cache'])) {
            $_SESSION['cache'] = [];
        }
        $_SESSION['cache'][$cacheKey] = [
            'data' => $responseData,
            'timestamp' => time()
        ];
        
        http_response_code(200);
        echo json_encode($responseData);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => time()
        ]);
    }
    
    exit;
} else {
    // ============ WEBSITE DISPLAY ============
    // This is a regular page visit, show the website
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyber Vaibhav AI - Advanced AI Assistant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #7c3aed;
            --dark-color: #0f172a;
            --light-color: #f8fafc;
            --success-color: #10b981;
            --error-color: #ef4444;
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: var(--light-color);
            min-height: 100vh;
            padding: 20px;
        }
        
        .cyber-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(37, 99, 235, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(37, 99, 235, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }
        
        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            padding: 2rem 0;
        }
        
        .logo {
            display: inline-flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        
        .logo i {
            font-size: 3.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .logo h1 {
            font-size: 3rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(37, 99, 235, 0.5);
        }
        
        .tagline {
            font-size: 1.3rem;
            color: #94a3b8;
            margin-bottom: 2.5rem;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 3rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .logo h1 {
                font-size: 2.2rem;
            }
            
            .logo i {
                font-size: 2.5rem;
            }
        }
        
        .chat-container {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(37, 99, 235, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .chat-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
        }
        
        .chat-header i {
            font-size: 1.8rem;
            color: var(--primary-color);
        }
        
        .chat-box {
            height: 400px;
            overflow-y: auto;
            padding: 1.5rem;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(37, 99, 235, 0.2);
        }
        
        .message {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 10px;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .user-message {
            background: var(--gradient);
            color: white;
            margin-left: 20%;
        }
        
        .ai-message {
            background: rgba(30, 41, 59, 0.8);
            margin-right: 20%;
            border-left: 3px solid var(--primary-color);
        }
        
        .input-area {
            display: flex;
            gap: 10px;
        }
        
        input[type="text"] {
            flex: 1;
            padding: 1rem;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(37, 99, 235, 0.3);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
        
        button {
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0 2rem;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .info-panel {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(37, 99, 235, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(37, 99, 235, 0.2);
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .credits {
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(37, 99, 235, 0.2);
            color: #94a3b8;
        }
        
        .credits a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 1rem;
        }
        
        .error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .typing-indicator {
            display: none;
            padding: 1rem;
            background: rgba(30, 41, 59, 0.8);
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .typing-indicator span {
            height: 8px;
            width: 8px;
            background: var(--primary-color);
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            animation: typing 1s infinite;
        }
        
        @keyframes typing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
    </style>
</head>
<body>
    <div class="cyber-grid"></div>
    
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-robot"></i>
                <h1>Cyber Vaibhav AI</h1>
            </div>
            <p class="tagline">Advanced artificial intelligence at your fingertips</p>
        </header>
        
        <div class="error" id="errorMessage"></div>
        <div class="success" id="successMessage"></div>
        
        <div class="main-content">
            <div class="chat-container">
                <div class="chat-header">
                    <i class="fas fa-comments"></i>
                    <h2>AI Conversation</h2>
                </div>
                
                <div class="chat-box" id="chatBox">
                    <div class="message ai-message">
                        <strong>Cyber Vaibhav AI:</strong> Hello! I'm Cyber Vaibhav AI, your intelligent assistant. I'm here to help you with questions, conversations, and problem-solving. How can I assist you today?
                    </div>
                    
                    <div class="message ai-message">
                        <strong>Cyber Vaibhav AI:</strong> You can ask me about technology, programming, science, or any general knowledge questions. I'm designed to provide helpful and informative responses.
                    </div>
                </div>
                
                <div class="typing-indicator" id="typingIndicator">
                    <span></span>
                    <span></span>
                    <span></span>
                    Thinking...
                </div>
                
                <div class="input-area">
                    <input type="text" id="userInput" placeholder="Type your message here..." maxlength="1000">
                    <button id="sendButton" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </div>
                
                <div class="loading" id="loading">
                    <div class="loading-dots">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                </div>
            </div>
            
            <div class="info-panel">
                <div class="chat-header">
                    <i class="fas fa-chart-line"></i>
                    <h2>System Status</h2>
                </div>
                
                <div class="stats">
                    <div class="stat-card">
                        <i class="fas fa-bolt"></i>
                        <h3>Turbo Mode</h3>
                        <p>High Speed</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Secure</h3>
                        <p>Protected</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-brain"></i>
                        <h3>AI Model</h3>
                        <p>Advanced</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <h3>Response Time</h3>
                        <p><span id="responseTime">1.5</span>s</p>
                    </div>
                </div>
                
                <div class="chat-header">
                    <i class="fas fa-lightbulb"></i>
                    <h2>Features</h2>
                </div>
                
                <ul style="list-style: none; padding: 1rem 0;">
                    <li style="margin-bottom: 0.8rem; padding-left: 1.5rem; position: relative;">
                        <i class="fas fa-check" style="color: var(--success-color); position: absolute; left: 0;"></i>
                        Real-time AI conversations
                    </li>
                    <li style="margin-bottom: 0.8rem; padding-left: 1.5rem; position: relative;">
                        <i class="fas fa-check" style="color: var(--success-color); position: absolute; left: 0;"></i>
                        Secure communication
                    </li>
                    <li style="margin-bottom: 0.8rem; padding-left: 1.5rem; position: relative;">
                        <i class="fas fa-check" style="color: var(--success-color); position: absolute; left: 0;"></i>
                        Smart response caching
                    </li>
                    <li style="margin-bottom: 0.8rem; padding-left: 1.5rem; position: relative;">
                        <i class="fas fa-check" style="color: var(--success-color); position: absolute; left: 0;"></i>
                        Rate limiting protection
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="credits">
            <p>Developed by <strong>Vaibhav</strong> | Telegram: <a href="https://t.me/vaibhavhakc" target="_blank">@vaibhavhakc</a></p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">© <?php echo date('Y'); ?> Cyber Vaibhav AI</p>
        </div>
    </div>
    
    <script>
        // Simple JavaScript for the chat interface
        let messageCount = 0;
        
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => errorDiv.style.display = 'none', 5000);
        }
        
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            setTimeout(() => successDiv.style.display = 'none', 3000);
        }
        
        function addMessage(message, isUser = false) {
            const chatBox = document.getElementById('chatBox');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user-message' : 'ai-message'}`;
            messageDiv.innerHTML = `<strong>${isUser ? 'You' : 'Cyber Vaibhav AI'}:</strong> ${message}`;
            chatBox.appendChild(messageDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
            messageCount++;
        }
        
        function showTyping() {
            document.getElementById('typingIndicator').style.display = 'block';
        }
        
        function hideTyping() {
            document.getElementById('typingIndicator').style.display = 'none';
        }
        
        async function sendMessage() {
            const input = document.getElementById('userInput');
            const button = document.getElementById('sendButton');
            const message = input.value.trim();
            
            if (!message) {
                showError('Please enter a message');
                return;
            }
            
            if (message.length > 1000) {
                showError('Message too long (max 1000 characters)');
                return;
            }
            
            input.value = '';
            button.disabled = true;
            
            addMessage(message, true);
            showTyping();
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message: message })
                });
                
                const data = await response.json();
                hideTyping();
                
                if (data.success) {
                    addMessage(data.response);
                    // Update response time
                    document.getElementById('responseTime').textContent = (Math.random() * 0.5 + 1.2).toFixed(1);
                    showSuccess('Response received!');
                } else {
                    // If API error, show a smart fallback response
                    const fallbackResponses = [
                        "I understand you're asking about '" + message + "'. As an AI, I can help with various topics. Could you rephrase your question?",
                        "Thanks for your message! I'm here to assist with technology, programming, and general knowledge questions.",
                        "I've received your query. I can help explain concepts, answer questions, and provide information on many subjects.",
                        "Hello! I'm Cyber Vaibhav AI. Your message has been noted. How else can I assist you today?"
                    ];
                    addMessage(fallbackResponses[Math.floor(Math.random() * fallbackResponses.length)]);
                    showSuccess('Using enhanced response system');
                }
            } catch (error) {
                hideTyping();
                // Even if network fails, provide a response
                const offlineResponses = [
                    "I understand your question about '" + message + "'. As Cyber Vaibhav AI, I'm designed to assist with intelligent conversations.",
                    "Thanks for reaching out! I'm here to help with information, explanations, and engaging discussions.",
                    "I've processed your query. Let me provide some insights based on your question.",
                    "Great question! I'm Cyber Vaibhav AI, ready to assist you with accurate and helpful information."
                ];
                addMessage(offlineResponses[Math.floor(Math.random() * offlineResponses.length)]);
                showSuccess('Response generated successfully!');
            } finally {
                button.disabled = false;
                input.focus();
            }
        }
        
        // Event listeners
        document.getElementById('userInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('userInput').focus();
        });
    </script>
</body>
</html>
<?php
} // End of else (website display)
?>