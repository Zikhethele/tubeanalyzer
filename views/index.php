<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free YouTube Channel Analyzer | Detailed Analytics & Insights - TubeAnalyzer</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Analyze any YouTube channel instantly. Get subscriber counts, video stats, engagement metrics, and growth insights. Free YouTube analytics tool for creators and marketers.">
    <meta name="keywords" content="youtube analytics, channel analyzer, youtube stats, social media analytics, influencer metrics, youtube insights">
    <meta name="author" content="TubeAnalyzer">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://tubeanalyzer.co.za">
    
    <!-- Open Graph -->
    <meta property="og:title" content="YouTube Channel Analyzer - Free Analytics Tool">
    <meta property="og:description" content="Get instant insights on any YouTube channel - subscribers, views, engagement rates, and more.">
    <meta property="og:image" content="https://tubeanalyzer.co.za/assets/og-image.jpg">
    <meta property="og:url" content="https://tubeanalyzer.co.za">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="YouTube Channel Analyzer">
    <meta name="twitter:description" content="Analyze any YouTube channel instantly with our free tool.">
    <meta name="twitter:image" content="https://tubeanalyzer.co.za/assets/twitter-card.jpg">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebApplication",
      "name": "YouTube Channel Analyzer",
      "description": "Free tool to analyze YouTube channel statistics and metrics",
      "url": "https://tubeanalyzer.co.za",
      "applicationCategory": "AnalyticsApplication",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "reviewCount": "1247"
      }
    }
    </script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .hero {
            text-align: center;
            padding: 4rem 2rem 2rem;
            color: white;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: fadeInDown 0.8s ease;
        }
        
        .hero p {
            font-size: 1.3rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease 0.2s backwards;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 2.5rem;
            animation: scaleIn 0.6s ease 0.4s backwards;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .spinner {
            display: none;
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .spinner.active { display: block; }
        
        .spinner-icon {
            width: 48px;
            height: 48px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        .result {
            margin-top: 2rem;
            padding: 1.5rem;
            border-radius: 12px;
            display: none;
        }
        
        .result.success {
            background: #e8f5e9;
            border: 2px solid #66bb6a;
            color: #2e7d32;
        }
        
        .result.error {
            background: #ffebee;
            border: 2px solid #ef5350;
            color: #d32f2f;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #667eea;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
        }
        
        .feature {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature h3 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .hero p { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1>🎬 YouTube Channel Analyzer</h1>
        <p>Get instant insights on any YouTube channel - Free & Fast</p>
    </div>
    
    <div class="container">
        <div class="card">
            <form id="analyzeForm" method="POST" action="analyze.php">
                <div class="input-group">
                    <label for="channel">Channel Name</label>
                    <input type="text" id="channel" name="channel" placeholder="e.g., MrBeast" required>
                </div>
                
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="e.g., you@example.com" required>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <span id="btnText">🚀 Analyze Channel</span>
                </button>
            </form>
            
            <div class="spinner" id="spinner">
                <div class="spinner-icon"></div>
                <p style="margin-top: 1rem; color: #667eea;">Analyzing channel...</p>
            </div>
            
            <div class="result" id="result"></div>
        </div>
    </div>
    
    <div class="features">
        <div class="feature">
            <div class="feature-icon">⚡</div>
            <h3>Instant Analysis</h3>
            <p>Get comprehensive channel data in seconds</p>
        </div>
        <div class="feature">
            <div class="feature-icon">📧</div>
            <h3>Email Reports</h3>
            <p>Beautiful reports delivered to your inbox</p>
        </div>
        <div class="feature">
            <div class="feature-icon">📈</div>
            <h3>Growth Insights</h3>
            <p>Track trends and top-performing content</p>
        </div>
        <div class="feature">
            <div class="feature-icon">🔒</div>
            <h3>Secure & Private</h3>
            <p>Your data is safe with us</p>
        </div>
    </div>
    
    <script>
        document.getElementById('analyzeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('spinner');
            const result = document.getElementById('result');
            
            // Show loading state
            submitBtn.disabled = true;
            btnText.textContent = 'Analyzing...';
            spinner.classList.add('active');
            result.style.display = 'none';
            
            try {
                const response = await fetch('analyze.php', {
                    method: 'POST',
                    body: formData
                });
                // const text = await response.text();
                // console.log(text);
                // const data = JSON.parse(text);

                const data = await response.json();

                result.style.display = 'block';
                
                if (data.success) {
                    result.className = 'result success';
                    result.innerHTML = `
                        <h3>✅ ${data.message}</h3>
                        <p>We've sent a detailed report to your email with visualizations and insights.</p>
                    `;
                } else {
                    result.className = 'result error';
                    result.innerHTML = `<h3>❌ ${data.message}</h3>`;
                }
                
            } catch (error) {
                result.style.display = 'block';
                result.className = 'result error';
                result.innerHTML = `<h3>❌ An error occurred. Please try again.</h3>`;
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = '🚀 Analyze Channel';
                spinner.classList.remove('active');
            }
        });
    </script>
</body>
</html>
