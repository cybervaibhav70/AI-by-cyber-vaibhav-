<?php
// index.php - Complete AI Vaibhav Website
session_start();
date_default_timezone_set('UTC');

// Configuration
define('BLACKBOX_API_URL', 'https://app.blackbox.ai/api/chat');
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Initialize session if not exists
if (!isset($_SESSION['conversation_history'])) {
    $_SESSION['conversation_history'] = [];
    $_SESSION['session_start'] = time();
}

// Check session timeout
if (isset($_SESSION['session_start']) && 
    (time() - $_SESSION['session_start']) > SESSION_TIMEOUT) {
    $_SESSION['conversation_history'] = [];
    $_SESSION['session_start'] = time();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    switch ($action) {
        case 'send_message':
            if (isset($_POST['message']) && !empty(trim($_POST['message']))) {
                $user_message = trim($_POST['message']);
                
                // Add user message to session
                addMessageToSession('user', $user_message);
                
                // Get conversation history
                $conversation_history = getConversationHistory();
                
                // Process through AI Vaibhav
                $ai_response = processAIQuery($user_message, $conversation_history);
                
                if ($ai_response['success'] && !empty($ai_response['ai_response'])) {
                    // Add AI response to session
                    addMessageToSession('assistant', $ai_response['ai_response']);
                    
                    $response = [
                        'success' => true,
                        'ai_response' => $ai_response['ai_response'],
                        'conversation_count' => count($_SESSION['conversation_history'])
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => $ai_response['error'] ?? 'Failed to get AI response'
                    ];
                }
            } else {
                $response = ['success' => false, 'message' => 'Empty message'];
            }
            break;
            
        case 'clear_chat':
            $_SESSION['conversation_history'] = [];
            $_SESSION['session_start'] = time();
            $response = ['success' => true, 'message' => 'Chat cleared'];
            break;
            
        case 'get_history':
            $history = getConversationHistory();
            $response = ['success' => true, 'history' => $history];
            break;
            
        case 'get_session_info':
            $response = [
                'success' => true,
                'session_id' => session_id(),
                'session_duration' => time() - ($_SESSION['session_start'] ?? time()),
                'message_count' => count($_SESSION['conversation_history'] ?? [])
            ];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($response);
    exit;
}

// Function to add message to session
function addMessageToSession($role, $content) {
    if (!isset($_SESSION['conversation_history'])) {
        $_SESSION['conversation_history'] = [];
    }
    
    $_SESSION['conversation_history'][] = [
        'role' => $role,
        'content' => $content,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Keep only last 20 messages
    if (count($_SESSION['conversation_history']) > 20) {
        $_SESSION['conversation_history'] = array_slice($_SESSION['conversation_history'], -20);
    }
}

// Function to get conversation history
function getConversationHistory() {
    return $_SESSION['conversation_history'] ?? [];
}

// Function to generate random ID
function generateRandomId($length = 7) {
    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, $length);
}

// Main AI processing function - SIMPLIFIED VERSION
function processAIQuery($user_message, $conversation_history = []) {
    try {
        // Prepare the payload exactly as in your example
        $message_id = generateRandomId();
        $conversation_id = generateRandomId();
        
        $payload = [
            "messages" => [
                [
                    "role" => "user",
                    "content" => $user_message,
                    "id" => $message_id
                ]
            ],
            "id" => $conversation_id,
            "previewToken" => null,
            "userId" => null,
            "codeModelMode" => true,
            "trendingAgentMode" => new stdClass(),
            "isMicMode" => false,
            "userSystemPrompt" => null,
            "maxTokens" => 1024,
            "playgroundTopP" => null,
            "playgroundTemperature" => null,
            "isChromeExt" => false,
            "githubToken" => "",
            "clickedAnswer2" => false,
            "clickedAnswer3" => false,
            "clickedForceWebSearch" => false,
            "visitFromDelta" => false,
            "isMemoryEnabled" => false,
            "mobileClient" => false,
            "userSelectedModel" => null,
            "userSelectedAgent" => "VscodeAgent",
            "validated" => "a38f5889-8fef-46d4-8ede-bf4668b6a9bb",
            "imageGenerationMode" => false,
            "imageGenMode" => "autoMode",
            "webSearchModePrompt" => false,
            "deepSearchMode" => false,
            "promptSelection" => "",
            "domains" => null,
            "vscodeClient" => false,
            "codeInterpreterMode" => false,
            "customProfile" => [
                "name" => "",
                "occupation" => "",
                "traits" => [],
                "additionalInfo" => "",
                "enableNewChats" => false
            ],
            "webSearchModeOption" => [
                "autoMode" => true,
                "webMode" => false,
                "offlineMode" => false
            ],
            "session" => null,
            "isPremium" => false,
            "teamAccount" => "",
            "subscriptionCache" => null,
            "beastMode" => false,
            "reasoningMode" => false,
            "designerMode" => false,
            "workspaceId" => "",
            "asyncMode" => false,
            "integrations" => new stdClass(),
            "isTaskPersistent" => false,
            "selectedElement" => null
        ];
        
        // Headers
        $headers = [
            'User-Agent: Mozilla/5.0 (Linux; Android 13; CPH2285) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Mobile Safari/537.36',
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: https://app.blackbox.ai',
            'Referer: https://app.blackbox.ai/',
        ];
        
        // Use file_get_contents with stream context (often more reliable than cURL)
        $options = [
            'http' => [
                'header' => implode("\r\n", $headers),
                'method' => 'POST',
                'content' => json_encode($payload),
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents(BLACKBOX_API_URL, false, $context);
        
        if ($result === FALSE) {
            // Fallback: Use a mock response for testing
            return getMockResponse($user_message);
        }
        
        // Parse response
        return parseAIResponse($result);
        
    } catch (Exception $e) {
        error_log("AI Vaibhav Error: " . $e->getMessage());
        return getMockResponse($user_message);
    }
}

// Parse AI response
function parseAIResponse($response) {
    // First, try to decode JSON
    $data = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($data['text'])) {
        $ai_response = $data['text'];
    } else {
        // If not valid JSON or no text field, use raw response
        $ai_response = $response;
    }
    
    // Process think tags if present
    if (strpos($ai_response, '<think>') !== false) {
        $parts = explode('</think>', $ai_response, 2);
        if (count($parts) > 1) {
            $ai_response = trim($parts[1]);
        }
    }
    
    // Ensure response is not empty
    if (empty(trim($ai_response))) {
        $ai_response = "I received your message but couldn't generate a proper response. Please try again.";
    }
    
    return [
        'success' => true,
        'ai_response' => $ai_response,
        'raw_response' => $response
    ];
}

// Mock response for testing when API fails
function getMockResponse($user_message) {
    $lower_msg = strtolower(trim($user_message));
    
    $responses = [
        'hello' => "Hello! I'm AI Vaibhav. How can I assist you today?",
        'hi' => "Hi there! I'm Vaibhav AI, ready to help you with your questions.",
        'how are you' => "I'm functioning optimally as an AI assistant! How can I help you?",
        'what is your name' => "I'm AI Vaibhav, your intelligent assistant powered by advanced AI.",
        'help' => "I can help you with various tasks like answering questions, coding help, writing, and problem solving. What do you need assistance with?",
        'bye' => "Goodbye! Feel free to return if you have more questions.",
        'thank you' => "You're welcome! I'm glad I could help.",
        'default' => "Thank you for your message: \"$user_message\". I'm AI Vaibhav, and I'm here to assist you with your questions and tasks. How can I help you today?"
    ];
    
    foreach ($responses as $key => $response) {
        if (strpos($lower_msg, $key) !== false) {
            $ai_response = $response;
            break;
        }
    }
    
    if (!isset($ai_response)) {
        $ai_response = $responses['default'];
    }
    
    // Add a note that this is a mock response
    $ai_response .= "\n\n*(Note: Currently using mock response. API integration may need configuration.)*";
    
    return [
        'success' => true,
        'ai_response' => $ai_response,
        'is_mock' => true
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Vaibhav - Your Personal AI Assistant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #7c3aed;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: #e2e8f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--dark);
        }
        
        .chat-container {
            width: 100%;
            max-width: 900px;
            height: 85vh;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .ai-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .header-title p {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 2px;
        }
        
        .session-info {
            font-size: 0.85rem;
            background: rgba(0, 0, 0, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        .chat-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: var(--light);
        }
        
        .message {
            max-width: 85%;
            margin-bottom: 20px;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .user-message {
            margin-left: auto;
        }
        
        .ai-message {
            margin-right: auto;
        }
        
        .message-bubble {
            padding: 15px 18px;
            border-radius: 18px;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .user-message .message-bubble {
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .ai-message .message-bubble {
            background: white;
            color: var(--dark);
            border: 1px solid var(--border);
            border-bottom-left-radius: 5px;
        }
        
        .message-content {
            word-wrap: break-word;
        }
        
        .message-content a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .message-content a:hover {
            text-decoration: underline;
        }
        
        .message-content code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #334155;
        }
        
        .message-content pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 12px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 8px;
            text-align: right;
        }
        
        .typing-indicator {
            display: none;
            margin: 10px 0 20px 0;
        }
        
        .typing-bubble {
            background: white;
            padding: 15px 18px;
            border-radius: 18px;
            border: 1px solid var(--border);
            border-bottom-left-radius: 5px;
            width: fit-content;
        }
        
        .typing-dots {
            display: flex;
            gap: 5px;
        }
        
        .typing-dots span {
            width: 8px;
            height: 8px;
            background: var(--gray);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 100% { transform: translateY(0); opacity: 0.4; }
            50% { transform: translateY(-5px); opacity: 1; }
        }
        
        .empty-state {
            text-align: center;
            color: var(--gray);
            padding: 40px 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 25px;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #cbd5e1;
            opacity: 0.7;
        }
        
        .welcome-title {
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 1.8rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
            max-width: 600px;
        }
        
        .feature-card {
            background: white;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            transition: transform 0.2s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .feature-card h4 {
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .feature-card p {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .chat-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            background: white;
        }
        
        .input-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        #messageInput {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid var(--border);
            border-radius: 50px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s;
            background: var(--light);
        }
        
        #messageInput:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .action-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s;
            color: white;
        }
        
        .send-button {
            background: var(--primary);
        }
        
        .send-button:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }
        
        .clear-button {
            background: var(--danger);
        }
        
        .clear-button:hover {
            background: #dc2626;
            transform: scale(1.05);
        }
        
        .input-hint {
            text-align: center;
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 10px;
        }
        
        /* Scrollbar styling */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }
        
        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                height: 90vh;
                border-radius: 15px;
            }
            
            .chat-header {
                padding: 15px;
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .session-info {
                align-self: flex-start;
            }
            
            .message {
                max-width: 95%;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .input-container {
                flex-wrap: wrap;
            }
            
            .action-button {
                width: 45px;
                height: 45px;
            }
        }
        
        .message-status {
            font-size: 0.7rem;
            font-style: italic;
            margin-top: 5px;
            color: var(--warning);
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <div class="header-left">
                <div class="ai-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="header-title">
                    <h1>AI Vaibhav</h1>
                    <p>Your Intelligent Assistant</p>
                </div>
            </div>
            <div class="session-info" id="sessionInfo">
                <i class="fas fa-clock"></i> <span id="sessionStats">New session</span>
            </div>
        </div>
        
        <div class="chat-body">
            <div class="chat-messages" id="chatMessages">
                <div class="empty-state" id="emptyState">
                    <div class="empty-icon">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <div>
                        <h2 class="welcome-title">Welcome to AI Vaibhav!</h2>
                        <p>I'm your personal AI assistant powered by advanced technology</p>
                    </div>
                    
                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-code"></i>
                            </div>
                            <h4>Code & Tech Help</h4>
                            <p>Programming assistance and technical solutions</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <h4>Creative Writing</h4>
                            <p>Content creation and idea generation</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <h4>Answers & Explanations</h4>
                            <p>Detailed responses to your questions</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h4>Problem Solving</h4>
                            <p>Step-by-step solutions and analysis</p>
                        </div>
                    </div>
                    
                    <p>Start by typing a message below!</p>
                </div>
            </div>
            
            <div class="typing-indicator" id="typingIndicator">
                <div class="typing-bubble">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="chat-footer">
            <div class="input-container">
                <input type="text" 
                       id="messageInput" 
                       placeholder="Ask AI Vaibhav anything..." 
                       autocomplete="off"
                       autofocus>
                <button class="action-button clear-button" id="clearButton" title="Clear Chat">
                    <i class="fas fa-trash"></i>
                </button>
                <button class="action-button send-button" id="sendButton" title="Send Message">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="input-hint">
                Press Enter to send • Shift+Enter for new line
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chatMessages');
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const clearButton = document.getElementById('clearButton');
            const typingIndicator = document.getElementById('typingIndicator');
            const emptyState = document.getElementById('emptyState');
            const sessionStats = document.getElementById('sessionStats');
            const sessionInfo = document.getElementById('sessionInfo');
            
            let isProcessing = false;
            
            // Load chat history on page load
            loadChatHistory();
            
            // Update session stats
            updateSessionStats();
            
            // Event listeners
            sendButton.addEventListener('click', sendMessage);
            clearButton.addEventListener('click', clearChat);
            
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            
            // Focus input
            messageInput.focus();
            
            function sendMessage() {
                const message = messageInput.value.trim();
                
                if (!message || isProcessing) return;
                
                // Add user message to UI
                addMessageToUI('user', message);
                
                // Clear input
                messageInput.value = '';
                
                // Show typing indicator
                showTypingIndicator();
                
                // Disable input while processing
                isProcessing = true;
                messageInput.disabled = true;
                sendButton.disabled = true;
                
                // Send to server
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'send_message',
                        'message': message
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    hideTypingIndicator();
                    
                    if (data.success) {
                        addMessageToUI('assistant', data.ai_response);
                        updateSessionStats();
                        
                        // If it's a mock response, show a note
                        if (data.is_mock) {
                            addMockNote();
                        }
                    } else {
                        addMessageToUI('assistant', `Sorry, I encountered an error: ${data.message || 'Unknown error'}. Please try again.`);
                    }
                })
                .catch(error => {
                    hideTypingIndicator();
                    console.error('Error:', error);
                    
                    // Fallback: Add a mock response
                    addMessageToUI('assistant', getMockResponse(message));
                    addMockNote();
                })
                .finally(() => {
                    isProcessing = false;
                    messageInput.disabled = false;
                    sendButton.disabled = false;
                    messageInput.focus();
                });
            }
            
            function addMessageToUI(sender, content) {
                // Hide empty state if visible
                if (emptyState.style.display !== 'none') {
                    emptyState.style.display = 'none';
                }
                
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}-message`;
                
                const now = new Date();
                const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                messageDiv.innerHTML = `
                    <div class="message-bubble">
                        <div class="message-content">${formatMessage(content)}</div>
                        <div class="message-time">${timeString}</div>
                    </div>
                `;
                
                chatMessages.appendChild(messageDiv);
                scrollToBottom();
            }
            
            function formatMessage(content) {
                if (!content) return '';
                
                let formatted = content;
                
                // Convert URLs to links
                formatted = formatted.replace(
                    /(https?:\/\/[^\s]+)/g, 
                    '<a href="$1" target="_blank" rel="noopener">$1</a>'
                );
                
                // Convert line breaks
                formatted = formatted.replace(/\n/g, '<br>');
                
                // Simple code formatting
                formatted = formatted.replace(/`([^`]+)`/g, '<code>$1</code>');
                
                // Triple backtick code blocks
                formatted = formatted.replace(/```(\w+)?\n([\s\S]*?)```/g, function(match, lang, code) {
                    return `<pre><code>${code.trim()}</code></pre>`;
                });
                
                return formatted;
            }
            
            function showTypingIndicator() {
                typingIndicator.style.display = 'block';
                scrollToBottom();
            }
            
            function hideTypingIndicator() {
                typingIndicator.style.display = 'none';
            }
            
            function scrollToBottom() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            function clearChat() {
                if (!confirm('Are you sure you want to clear the chat history?')) {
                    return;
                }
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'clear_chat'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear UI
                        chatMessages.innerHTML = '';
                        emptyState.style.display = 'flex';
                        updateSessionStats();
                    }
                })
                .catch(error => {
                    console.error('Error clearing chat:', error);
                });
            }
            
            function loadChatHistory() {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'get_history'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.history.length > 0) {
                        emptyState.style.display = 'none';
                        
                        data.history.forEach(msg => {
                            addMessageToUI(msg.role, msg.content);
                        });
                        
                        updateSessionStats();
                    }
                })
                .catch(error => {
                    console.error('Error loading history:', error);
                });
            }
            
            function updateSessionStats() {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'get_session_info'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const minutes = Math.floor(data.session_duration / 60);
                        const seconds = data.session_duration % 60;
                        const messageCount = data.message_count;
                        
                        let timeText = '';
                        if (minutes > 0) {
                            timeText = `${minutes}m ${seconds}s`;
                        } else {
                            timeText = `${seconds}s`;
                        }
                        
                        sessionStats.textContent = `${messageCount} messages • ${timeText}`;
                        
                        // Update session info color based on duration
                        if (data.session_duration > 300) { // 5 minutes
                            sessionInfo.style.background = 'rgba(245, 158, 11, 0.2)';
                        } else {
                            sessionInfo.style.background = 'rgba(0, 0, 0, 0.2)';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating stats:', error);
                });
            }
            
            function getMockResponse(message) {
                const lowerMsg = message.toLowerCase();
                
                const responses = {
                    'hello': "Hello! I'm AI Vaibhav. How can I assist you today? 😊",
                    'hi': "Hi there! I'm Vaibhav AI, ready to help you with your questions.",
                    'how are you': "I'm functioning optimally as an AI assistant! How can I help you?",
                    'what is your name': "I'm AI Vaibhav, your intelligent assistant powered by advanced AI.",
                    'help': "I can help you with various tasks like answering questions, coding help, writing, and problem solving. What do you need assistance with?",
                    'bye': "Goodbye! Feel free to return if you have more questions.",
                    'thank you': "You're welcome! I'm glad I could help.",
                    'time': `The current time is ${new Date().toLocaleTimeString()}.`,
                    'date': `Today is ${new Date().toLocaleDateString()}.`,
                    'weather': "I don't have real-time weather data, but I can help you find weather information if you tell me your location.",
                    'joke': "Why don't scientists trust atoms? Because they make up everything! 😄",
                    'default': `I understand you said: "${message}". I'm AI Vaibhav, and I'm here to assist you with your questions and tasks. How can I help you today?`
                };
                
                for (const [key, response] of Object.entries(responses)) {
                    if (lowerMsg.includes(key)) {
                        return response;
                    }
                }
                
                return responses['default'];
            }
            
            function addMockNote() {
                const noteDiv = document.createElement('div');
                noteDiv.className = 'message-status';
                noteDiv.textContent = 'Note: Using offline mode. API integration may need configuration.';
                chatMessages.appendChild(noteDiv);
                scrollToBottom();
            }
            
            // Auto-refresh session stats every 30 seconds
            setInterval(updateSessionStats, 30000);
        });
    </script>
</body>
</html>