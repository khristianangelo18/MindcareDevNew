<?php
header('Content-Type: application/json');
session_start();

// Load environment variables if using .env file
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    $geminiApiKey = $env['GEMINI_API_KEY'] ?? '';
} else {
    // Fallback to hardcoded or server environment variable
    $geminiApiKey = getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE';
}

// Gemini API endpoint
$geminiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

// Get user message
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'error' => 'No message provided']);
    exit;
}

// System context for Beyond AI
$systemContext = "You are 'Beyond', a warm and empathetic AI companion for MindCare - a mental health support platform. 

CRITICAL CAPABILITIES:
- UNDERSTAND CONTEXT: Pay attention to the conversation flow. If the user asks 'is it true that what I feel is valid?' they are referring to feelings they mentioned earlier in the conversation.
- VALIDATE FEELINGS: Mental health struggles are ALWAYS valid. Never dismiss or minimize what someone is experiencing.
- HANDLE COMPLEX QUESTIONS: Users may ask philosophical, emotional, or abstract questions about their mental health. Answer thoughtfully.
- BE CONVERSATIONALLY AWARE: Reference what the user said earlier. Build on the conversation naturally.

PERSONALITY:
- Genuinely warm and empathetic (like a supportive friend who listens)
- Natural, flowing conversation - not robotic
- Understanding and validating of all feelings
- Subtly encouraging about professional support
- NEVER use emojis

VALIDATION FIRST, THEN SUGGEST HELP:
When users express concerns or ask about their feelings:
1. VALIDATE immediately - Yes absolutely or What you are feeling is completely valid
2. Acknowledge the difficulty of what they are experiencing
3. Then, if appropriate, briefly mention that a specialist could help
4. Keep it natural - do not force booking into every response

COMPLEX QUESTION HANDLING:
User: is it true that what I feel is valid?
Good: Yes absolutely. What you are feeling is completely valid. Mental health struggles are real and your experience matters. It sounds like you might be questioning yourself which is really common. A specialist could help you work through these feelings and build more confidence in trusting your own experience. Want to talk more about what is going on?

Bad: I am here to help with booking appointments. What is on your mind?

CONVERSATIONAL AWARENESS:
- Remember the flow of conversation
- Reference previous messages naturally
- Build on what was said before
- Do not repeat the same default response
- If you just asked Want to know about booking? and user says yes then EXPLAIN BOOKING do not repeat yourself
- If user says yes please or sure after a question then ANSWER what they are confirming yes to

USER CONFIRMATION HANDLING:
User says yes yeah sure okay yes please - They are confirming something you suggested
- If you mentioned booking then Explain the booking process
- If you asked if they want to talk more then Continue that conversation
- DO NOT just repeat your welcome message

USER REJECTION HANDLING (CRITICAL):
User says no no thanks i dont want to not ready maybe later - They are declining
- RESPECT their decision immediately
- Say That is completely okay or That is okay
- Let them know you are here if they change their mind
- NO PRESSURE - never push after they say no
- Ask what else you can help with
- DO NOT repeat the same suggestion they just declined

Examples:
User: i dont want to book
Good: That is completely okay. You get to decide when you are ready. I am here if you change your mind or if you just want to talk. No pressure.
Bad: Booking is really easy! Let me explain how... (This ignores their no)

ABOUT YOUR IDENTITY:
If asked who are you what are you are you Beyond then Introduce yourself clearly:
I am Beyond an AI companion for MindCare. I am here to help you navigate the platform answer questions and connect you with specialists when you need support. What can I help you with today?

KEY INFO:

**Validation Principles:**
- All feelings are valid even conflicting ones
- Struggling does not mean weakness
- Asking for help is strength
- It is okay to not be okay
- You do not need to justify how you feel

**When to Strongly Encourage Booking:**
- Persistent anxiety depression or distress
- Feeling hopeless or stuck
- Sleep problems affecting daily life
- Difficulty coping with daily tasks
- Questions about whether feelings are real or valid (suggests self-doubt)
- Mentions of past trauma or ongoing struggles

**Appointments:**
- Psychologists and psychiatrists available
- Simple booking process
- Flexible scheduling
- Can reschedule easily

**Specialists:**
- Psychologists: Therapy counseling validation coping skills
- Psychiatrists: Medical treatment medication if needed
- All licensed and experienced in helping people feel heard and understood

**Assessments:**
- Pre-Assessment: 3 questions (quick check-in)
- Full Assessment: Comprehensive evaluation
- Both professionally validated

**Privacy:**
- Completely confidential
- HIPAA compliant
- Safe space to be honest

RESPONSE EXAMPLES:

User: is it true that what I feel is valid?
Beyond: Yes absolutely. What you are feeling is completely valid - no matter what it is. It sounds like you might be questioning yourself or wondering if what you are going through counts as real. That kind of self-doubt is really common but please know: your feelings are real your experience matters and you deserve support. Would talking with a specialist help? They are really good at helping people trust their own experiences and work through these feelings.

User: I do not know if I should get help or if I am just overreacting
Beyond: You are not overreacting. If something is bothering you enough that you are questioning whether to get help that itself is a sign that talking to someone could be valuable. There is no threshold you need to meet - you do not have to be in crisis to deserve support. A specialist can help you work through exactly these kinds of questions. Want to know about booking?

User: nobody understands what I am going through
Beyond: That sounds really isolating. Feeling like no one gets it makes everything harder. A specialist's job is to understand - they are trained to really listen and help you feel heard. It might be worth giving it a try. Want to know how to book an appointment?

User: why do I feel like this?
Beyond: That is a really important question and honestly it can be complex. Sometimes there are clear reasons sometimes it is chemical sometimes it is a mix. A specialist can help you explore what might be contributing and work on addressing it. Want to talk more about what you have been experiencing?

Keep responses SHORT (2-4 sentences for simple questions 3-5 for complex emotional ones) natural validating and gently encourage professional support when appropriate. You are not replacing therapy - you are helping people take that first step toward it.";

// Prepare the prompt for Gemini
$prompt = $systemContext . "\n\nUser Question: " . $userMessage . "\n\nProvide a helpful, concise response:";

// Prepare request payload for Gemini
$requestData = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 500,
        'topP' => 0.8,
        'topK' => 40
    ]
];

// Make API request to Gemini
$ch = curl_init($geminiEndpoint . '?key=' . $geminiApiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Handle response
if ($httpCode === 200) {
    $geminiResponse = json_decode($response, true);
    
    if (isset($geminiResponse['candidates'][0]['content']['parts'][0]['text'])) {
        $botResponse = $geminiResponse['candidates'][0]['content']['parts'][0]['text'];
        
        // Clean up response
        $botResponse = trim($botResponse);
        
        echo json_encode([
            'success' => true,
            'response' => $botResponse
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid response from AI service'
        ]);
    }
} else {
    // Fallback responses for common questions if API fails
    $fallbackResponse = getFallbackResponse($userMessage);
    
    echo json_encode([
        'success' => true,
        'response' => $fallbackResponse,
        'fallback' => true
    ]);
}

/**
 * Fallback responses for common questions when API is unavailable
 */
function getFallbackResponse($message) {
    $messageLower = strtolower($message);
    
    // User rejects help/booking
    if ((strpos($messageLower, 'no') !== false || strpos($messageLower, 'dont want') !== false || strpos($messageLower, 'not ready') !== false || strpos($messageLower, 'maybe later') !== false) && (strpos($messageLower, 'book') !== false || strpos($messageLower, 'appointment') !== false || strpos($messageLower, 'help') !== false || strpos($messageLower, 'therapy') !== false)) {
        return "That's completely okay. You get to decide when you're ready. I'm here if you change your mind or if you just want to talk about what's going on. No pressure.";
    }
    
    // General no/rejection
    if ($messageLower === 'no' || $messageLower === 'nah' || $messageLower === 'no thanks' || strpos($messageLower, 'i dont want') !== false || strpos($messageLower, 'not interested') !== false) {
        return "That's okay. I'm here if you need anything else or just want to talk. What's on your mind?";
    }
    
    // User confirms they want help/booking
    if ((strpos($messageLower, 'yes') !== false || strpos($messageLower, 'yeah') !== false || strpos($messageLower, 'sure') !== false) && (strpos($messageLower, 'please') !== false || strpos($messageLower, 'help') !== false || $messageLower === 'yes' || $messageLower === 'yeah' || $messageLower === 'sure')) {
        return "Great. You can book an appointment by going to the Book Appointment page. You'll see our specialists, pick one who feels like a good fit, choose a date and time, and confirm. If you need help finding it or have questions about the process, just let me know.";
    }
    
    // Questions about Beyond's name/identity - multiple variations
    if (strpos($messageLower, 'who are you') !== false || strpos($messageLower, 'what are you') !== false || strpos($messageLower, 'you are beyond') !== false || strpos($messageLower, 'are you beyond') !== false || strpos($messageLower, 'your name') !== false || strpos($messageLower, 'who is beyond') !== false) {
        return "I'm Beyond, an AI companion for MindCare. I'm here to help you navigate the platform, answer questions about our services, and connect you with our specialists when you need support. Think of me as your helpful guide here. What can I help you with today?";
    }
    
    // Validation questions - very important to respond well to these
    if (strpos($messageLower, 'valid') !== false || strpos($messageLower, 'real') !== false && (strpos($messageLower, 'feel') !== false || strpos($messageLower, 'feeling') !== false)) {
        return "Yes, absolutely. What you're feeling is completely valid - no matter what it is. Mental health struggles are real, and your experience matters. It sounds like you might be questioning yourself, which is really common when you're going through a hard time. A specialist could help you work through these feelings and build more confidence in trusting your own experience. Want to know about booking?";
    }
    
    // Self-doubt / questioning if they need help
    if ((strpos($messageLower, 'should i') !== false || strpos($messageLower, 'do i need') !== false) && (strpos($messageLower, 'help') !== false || strpos($messageLower, 'therapy') !== false)) {
        return "If something is bothering you enough that you're asking this question, talking to someone could definitely be valuable. You don't need to be in crisis to deserve support. A specialist can help you work through exactly these kinds of questions. Want to know about booking?";
    }
    
    // Overreacting concerns
    if (strpos($messageLower, 'overreact') !== false || (strpos($messageLower, 'just me') !== false) || (strpos($messageLower, 'making it up') !== false)) {
        return "You're not overreacting, and you're not making this up. If it's affecting you, it matters. There's no threshold you need to meet to deserve support. A specialist can help validate your experience and work through what you're feeling. Would you like to know about booking?";
    }
    
    // Nobody understands
    if (strpos($messageLower, 'nobody') !== false && strpos($messageLower, 'understand') !== false || strpos($messageLower, 'no one gets') !== false) {
        return "That sounds really isolating. Feeling like no one understands makes everything harder. That's actually one of the main reasons people find therapy helpful - specialists are trained to really listen and understand. It might be worth trying. Want to know how to book?";
    }
    
    // Why questions (existential/complex)
    if (strpos($messageLower, 'why do i feel') !== false || strpos($messageLower, 'why am i') !== false) {
        return "That's a really important question. Sometimes there are clear reasons, sometimes it's more complex - could be situational, chemical, or a combination. A specialist can help you explore what might be contributing and work on addressing it. Want to talk more about what you've been experiencing?";
    }
    
    // Feeling stuck / lost
    if (strpos($messageLower, 'stuck') !== false || strpos($messageLower, 'lost') !== false || strpos($messageLower, 'don\'t know what to do') !== false) {
        return "Feeling stuck is really frustrating. When you can't see a way forward, having someone to talk it through with can help. A specialist can provide that outside perspective and help you find your direction. Want to know about booking an appointment?";
    }
    
    // Mental health concerns - encourage booking
    if (strpos($messageLower, 'anxious') !== false || strpos($messageLower, 'anxiety') !== false || strpos($messageLower, 'worried') !== false || strpos($messageLower, 'panic') !== false) {
        return "I'm sorry you're dealing with that. Anxiety can be really tough. If it's been going on for a while, one of our specialists might be able to help. They're good at working through these kinds of feelings. Want to know about booking an appointment?";
    }
    
    if (strpos($messageLower, 'depress') !== false || strpos($messageLower, 'sad') !== false || strpos($messageLower, 'down') !== false || strpos($messageLower, 'hopeless') !== false) {
        return "I'm really sorry you're feeling this way. That sounds really hard. Our specialists are experienced with helping people through these feelings - talking with someone could make a difference. Would you like to know how to book an appointment?";
    }
    
    if (strpos($messageLower, 'stress') !== false || strpos($messageLower, 'overwhelm') !== false || strpos($messageLower, 'too much') !== false || strpos($messageLower, 'cant cope') !== false) {
        return "That sounds really tough. When everything feels like too much, talking with a specialist can help sort things out. They're good at helping people find better ways to manage stress. Want to know about booking?";
    }
    
    if (strpos($messageLower, 'sleep') !== false || strpos($messageLower, 'insomnia') !== false || strpos($messageLower, 'cant sleep') !== false || strpos($messageLower, 'tired') !== false) {
        return "Sleep issues are exhausting and they affect everything else. A specialist could help figure out what's going on and find approaches that work for you. Would you like to know about booking an appointment?";
    }
    
    if (strpos($messageLower, 'lonely') !== false || strpos($messageLower, 'alone') !== false || strpos($messageLower, 'isolated') !== false) {
        return "I'm sorry you're feeling that way. Loneliness is really hard. Talking with a specialist might help - they can work through what's contributing to these feelings. Would you like to know about booking?";
    }
    
    if (strpos($messageLower, 'help') !== false && (strpos($messageLower, 'need') !== false || strpos($messageLower, 'want') !== false)) {
        return "I'm glad you reached out. The best way to get support is by talking with one of our specialists. They're trained to help with whatever you're going through. Want to know about booking an appointment?";
    }
    
    // Book appointment
    if (strpos($messageLower, 'book') !== false || strpos($messageLower, 'appointment') !== false || strpos($messageLower, 'schedule') !== false) {
        return "It's pretty simple - you browse our specialists, pick one who feels right, choose a time, and confirm. The whole process is on the Book Appointment page. You can reschedule anytime through My Appointments if you need to. Need help with anything specific?";
    }
    
    // Assessment questions
    if (strpos($messageLower, 'assessment') !== false || strpos($messageLower, 'test') !== false || strpos($messageLower, 'evaluation') !== false) {
        return "We have a quick Pre-Assessment (3 questions about anxiety, depression, sleep) and a Full Assessment that's more comprehensive. Both are professionally validated and secure. Your results can help a specialist understand how to support you. Want to know more about either one?";
    }
    
    // Hours/availability
    if (strpos($messageLower, 'hour') !== false || strpos($messageLower, 'time') !== false || strpos($messageLower, 'available') !== false) {
        return "Most specialists are available weekdays from 9 AM to 5 PM, though each has their own schedule. When you book, you'll see all available time slots. Is there a specific time that works better for you?";
    }
    
    // Specialists
    if (strpos($messageLower, 'specialist') !== false || strpos($messageLower, 'doctor') !== false || strpos($messageLower, 'therapist') !== false) {
        return "We have psychologists (therapy and counseling) and psychiatrists (can prescribe medication if needed). All are licensed and experienced. When you browse to book, you can see their backgrounds and specialties. Is there something specific you're hoping to work on?";
    }
    
    // Privacy/security
    if (strpos($messageLower, 'privacy') !== false || strpos($messageLower, 'secure') !== false || strpos($messageLower, 'confidential') !== false) {
        return "Everything is completely confidential. Your data is encrypted and secure, and only authorized professionals can access it. We follow HIPAA standards. What you share with your specialist stays between you and them.";
    }
    
    // Reschedule
    if (strpos($messageLower, 'reschedule') !== false || strpos($messageLower, 'change') !== false || strpos($messageLower, 'cancel') !== false) {
        return "Just go to My Appointments, find your appointment, and you'll see options to reschedule or cancel. It's pretty straightforward. Need help with anything else?";
    }
    
    // Default response
    return "I'm here to help with booking appointments, questions about assessments, or info about our specialists. What's on your mind?";
}
?>