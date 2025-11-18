<!-- Beyond AI Chatbot Widget - MindCare Minimal Version -->
<!-- Quick actions can be toggled on/off to maximize conversation space -->

<style>
/* Beyond Chatbot - MindCare Theme - Minimal */
:root {
  --beyond-primary: #5ad0be;
  --beyond-primary-dark: #1aa592;
  --beyond-bubble-shadow: rgba(90, 208, 190, 0.4);
}

/* Floating Chat Button */
.beyond-chat-bubble {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, var(--beyond-primary) 0%, var(--beyond-primary-dark) 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 20px var(--beyond-bubble-shadow);
  transition: all 0.3s ease;
  z-index: 1001;
}

.beyond-chat-bubble:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 25px rgba(90, 208, 190, 0.6);
}

.beyond-chat-bubble svg {
  width: 28px;
  height: 28px;
  color: white;
}

/* Chat Window */
.beyond-chat-window {
  position: fixed;
  bottom: 100px;
  right: 30px;
  width: 380px;
  height: 550px;
  background: var(--card-bg, #ffffff);
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  display: none;
  flex-direction: column;
  overflow: hidden;
  z-index: 1000;
  animation: slideUp 0.3s ease;
  border: 1px solid var(--border-color, #e9edf5);
}

body.dark-mode .beyond-chat-window {
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
}

.beyond-chat-window.active {
  display: flex;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Chat Header */
.beyond-chat-header {
  background: linear-gradient(135deg, var(--beyond-primary) 0%, var(--beyond-primary-dark) 100%);
  color: white;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.beyond-chat-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.beyond-chat-avatar {
  width: 45px;
  height: 45px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.beyond-chat-header-info h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: white;
}

.beyond-chat-header-info p {
  margin: 0;
  font-size: 12px;
  opacity: 0.9;
  color: white;
}

.beyond-chat-close {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  font-size: 24px;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s;
}

.beyond-chat-close:hover {
  transform: rotate(90deg);
}

/* Messages Area */
.beyond-chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  background: var(--bg-light, #f8f9fa);
}

body.dark-mode .beyond-chat-messages {
  background: var(--bg-light, #1a1a1a);
}

.beyond-message {
  margin-bottom: 15px;
  display: flex;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.beyond-message.user {
  justify-content: flex-end;
  padding-right: 0;
}

.beyond-message.user .beyond-message-content {
  max-width: 85%;
  min-width: 80px;
}

.beyond-message-content {
  max-width: 75%;
  padding: 12px 16px;
  border-radius: 18px;
  font-size: 14px;
  line-height: 1.5;
  word-wrap: break-word;
  word-break: break-word;
  white-space: normal;
  overflow-wrap: break-word;
}

.beyond-message.bot .beyond-message-content {
  background: var(--card-bg, white);
  color: var(--text-dark, #333);
  border: 1px solid var(--border-color, #e0e0e0);
  border-radius: 18px 18px 18px 4px;
}

.beyond-message.user .beyond-message-content {
  background: linear-gradient(135deg, var(--beyond-primary) 0%, var(--beyond-primary-dark) 100%);
  color: white;
  border-radius: 18px 18px 4px 18px;
}

.beyond-message-time {
  font-size: 11px;
  color: var(--text-muted, #999);
  margin-top: 4px;
  padding: 0 4px;
}

/* Typing Indicator */
.beyond-typing-indicator {
  display: none;
  padding: 12px 16px;
  background: var(--card-bg, white);
  border-radius: 18px;
  border: 1px solid var(--border-color, #e0e0e0);
  width: fit-content;
}

.beyond-typing-indicator.active {
  display: block;
}

.beyond-typing-indicator span {
  height: 8px;
  width: 8px;
  background: var(--beyond-primary);
  border-radius: 50%;
  display: inline-block;
  margin-right: 4px;
  animation: bounce 1.4s infinite;
}

.beyond-typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.beyond-typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes bounce {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-8px);
  }
}

/* Quick Actions Container - Collapsible */
.beyond-quick-actions-container {
  border-top: 1px solid var(--border-color, #e9edf5);
  background: var(--card-bg, white);
}

.beyond-quick-toggle {
  width: 100%;
  padding: 8px 20px;
  background: transparent;
  border: none;
  color: var(--text-muted, #7a828e);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.3s;
}

.beyond-quick-toggle:hover {
  color: var(--beyond-primary);
}

.beyond-quick-toggle svg {
  width: 14px;
  height: 14px;
  transition: transform 0.3s;
}

.beyond-quick-toggle.open svg {
  transform: rotate(180deg);
}

/* Quick Actions */
.beyond-quick-actions {
  display: none;
  flex-wrap: wrap;
  gap: 6px;
  padding: 0 20px 12px 20px;
  background: var(--card-bg, white);
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
}

.beyond-quick-actions.show {
  display: flex;
  max-height: 200px;
}

body.dark-mode .beyond-quick-actions {
  background: var(--card-bg, #2d2d2d);
}

.beyond-quick-action-btn {
  background: var(--bg-light, #f8f9fa);
  border: 1px solid var(--border-color, #e0e0e0);
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.3s;
  color: var(--beyond-primary);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

body.dark-mode .beyond-quick-action-btn {
  background: var(--bg-light, #1a1a1a);
}

.beyond-quick-action-btn svg {
  width: 12px;
  height: 12px;
  flex-shrink: 0;
}

.beyond-quick-action-btn:hover {
  background: var(--beyond-primary);
  color: white;
  border-color: var(--beyond-primary);
}

/* Input Area */
.beyond-chat-input-area {
  padding: 15px 20px;
  background: var(--card-bg, white);
  border-top: 1px solid var(--border-color, #e0e0e0);
  display: flex;
  gap: 10px;
  align-items: center;
}

.beyond-chat-input {
  flex: 1;
  border: 1px solid var(--border-color, #e0e0e0);
  border-radius: 25px;
  padding: 10px 18px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.3s;
  background: var(--bg-light, white);
  color: var(--text-dark, #333);
}

.beyond-chat-input:focus {
  border-color: var(--beyond-primary);
}

.beyond-chat-send-btn {
  background: linear-gradient(135deg, var(--beyond-primary) 0%, var(--beyond-primary-dark) 100%);
  color: white;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
}

.beyond-chat-send-btn:hover {
  transform: scale(1.1);
}

.beyond-chat-send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Scrollbar */
.beyond-chat-messages::-webkit-scrollbar {
  width: 6px;
}

.beyond-chat-messages::-webkit-scrollbar-track {
  background: var(--bg-light, #f1f1f1);
}

.beyond-chat-messages::-webkit-scrollbar-thumb {
  background: var(--beyond-primary);
  border-radius: 10px;
}

.beyond-chat-messages::-webkit-scrollbar-thumb:hover {
  background: var(--beyond-primary-dark);
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .beyond-chat-window {
    width: calc(100vw - 20px);
    height: calc(100vh - 100px);
    right: 10px;
    bottom: 80px;
  }

  .beyond-chat-bubble {
    right: 20px;
    bottom: 20px;
  }
}
</style>

<!-- Floating Chat Button -->
<div class="beyond-chat-bubble" id="beyondChatBubble">
  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
  </svg>
</div>

<!-- Chat Window -->
<div class="beyond-chat-window" id="beyondChatWindow">
  <!-- Header -->
  <div class="beyond-chat-header">
    <div class="beyond-chat-header-left">
      <div class="beyond-chat-avatar">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="3"></circle>
          <path d="M12 1v6m0 6v6m6-12a9 9 0 0 1-6 8.5 9 9 0 0 1-6-8.5"></path>
          <line x1="12" y1="20" x2="12" y2="23"></line>
          <line x1="8" y1="23" x2="16" y2="23"></line>
        </svg>
      </div>
      <div class="beyond-chat-header-info">
        <h4>Beyond: AI Companion</h4>
        <p>Here to help you navigate MindCare</p>
      </div>
    </div>
    <button class="beyond-chat-close" id="beyondChatClose">×</button>
  </div>

  <!-- Messages Area -->
  <div class="beyond-chat-messages" id="beyondChatMessages">
    <div class="beyond-message bot">
      <div>
        <div class="beyond-message-content">
          Hi there! I'm <strong>Beyond</strong>, and I'm here to help you navigate MindCare and connect with the support you need.<br><br>
          Whether you have questions about booking an appointment, want to know about our assessments, or just need someone to talk to about what's on your mind - I'm here for you.<br><br>
          What brings you here today?
        </div>
        <div class="beyond-message-time">Just now</div>
      </div>
    </div>
  </div>

  <!-- Typing Indicator -->
  <div class="beyond-message bot" id="beyondTypingIndicator">
    <div class="beyond-typing-indicator">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>

  <!-- Quick Actions - Collapsible -->
  <div class="beyond-quick-actions-container">
    <button class="beyond-quick-toggle" id="beyondQuickToggle">
      <span>Quick Actions</span>
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"></polyline>
      </svg>
    </button>
    <div class="beyond-quick-actions" id="beyondQuickActions">
      <button class="beyond-quick-action-btn" onclick="sendBeyondQuickMessage('How do I book an appointment?')">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        Book
      </button>
      <button class="beyond-quick-action-btn" onclick="sendBeyondQuickMessage('What assessments are available?')">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
        Assessments
      </button>
      <button class="beyond-quick-action-btn" onclick="sendBeyondQuickMessage('What are your operating hours?')">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        Hours
      </button>
      <button class="beyond-quick-action-btn" onclick="sendBeyondQuickMessage('How do I reschedule my appointment?')">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="23 4 23 10 17 10"></polyline>
          <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
        </svg>
        Reschedule
      </button>
    </div>
  </div>

  <!-- Input Area -->
  <div class="beyond-chat-input-area">
    <input 
      type="text" 
      class="beyond-chat-input" 
      id="beyondChatInput" 
      placeholder="Type your message..."
      autocomplete="off"
    >
    <button class="beyond-chat-send-btn" id="beyondSendBtn">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="22" y1="2" x2="11" y2="13"></line>
        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
      </svg>
    </button>
  </div>
</div>

<script>
(function() {
  const chatBubble = document.getElementById('beyondChatBubble');
  const chatWindow = document.getElementById('beyondChatWindow');
  const chatClose = document.getElementById('beyondChatClose');
  const chatMessages = document.getElementById('beyondChatMessages');
  const chatInput = document.getElementById('beyondChatInput');
  const sendBtn = document.getElementById('beyondSendBtn');
  const typingIndicator = document.getElementById('beyondTypingIndicator');
  const quickToggle = document.getElementById('beyondQuickToggle');
  const quickActions = document.getElementById('beyondQuickActions');

  // Toggle chat window
  chatBubble.addEventListener('click', () => {
    chatWindow.classList.toggle('active');
    if (chatWindow.classList.contains('active')) {
      chatInput.focus();
    }
  });

  chatClose.addEventListener('click', () => {
    chatWindow.classList.remove('active');
  });

  // Toggle quick actions
  quickToggle.addEventListener('click', () => {
    quickActions.classList.toggle('show');
    quickToggle.classList.toggle('open');
  });

  // Send message on button click
  sendBtn.addEventListener('click', sendBeyondMessage);

  // Send message on Enter key
  chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendBeyondMessage();
    }
  });

  // Quick message function (global)
  window.sendBeyondQuickMessage = function(message) {
    chatInput.value = message;
    sendBeyondMessage();
    // Hide quick actions after selection
    quickActions.classList.remove('show');
    quickToggle.classList.remove('open');
  };

  // Main send message function
  async function sendBeyondMessage() {
    const message = chatInput.value.trim();
    if (!message) return;

    // Display user message
    displayBeyondMessage(message, 'user');
    chatInput.value = '';

    // Show typing indicator
    typingIndicator.classList.add('active');
    scrollBeyondToBottom();

    // Send to backend
    try {
      const response = await fetch('beyond_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: message })
      });

      const data = await response.json();

      // Hide typing indicator
      typingIndicator.classList.remove('active');

      // Display bot response
      if (data.success) {
        displayBeyondMessage(data.response, 'bot');
      } else {
        displayBeyondMessage('Sorry, I encountered an error. Please try again.', 'bot');
      }
    } catch (error) {
      typingIndicator.classList.remove('active');
      displayBeyondMessage('Sorry, I could not connect to the server. Please check your connection.', 'bot');
    }
  }

  // Display message in chat
  function displayBeyondMessage(text, sender) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `beyond-message ${sender}`;
    
    const time = new Date().toLocaleTimeString('en-US', { 
      hour: 'numeric', 
      minute: '2-digit',
      hour12: true 
    });

    // Convert newlines to <br> for better formatting
    const formattedText = text.replace(/\n/g, '<br>');

    messageDiv.innerHTML = `
      <div>
        <div class="beyond-message-content">${escapeBeyondHtml(formattedText)}</div>
        <div class="beyond-message-time">${time}</div>
      </div>
    `;

    chatMessages.appendChild(messageDiv);
    scrollBeyondToBottom();
  }

  // Scroll to bottom of messages
  function scrollBeyondToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  // Escape HTML to prevent XSS (but preserve <br> tags)
  function escapeBeyondHtml(text) {
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;')
      .replace(/&lt;br&gt;/g, '<br>'); // Restore <br> tags
  }
})();
</script>