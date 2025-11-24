<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Sarap Local</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .success {
            color: #28a745;
        }
        
        .error {
            color: #dc3545;
        }
        
        .warning {
            color: #ffc107;
        }
        
        .info {
            color: #17a2b8;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Migration Tool</h1>
        <p class="subtitle">Sarap Local - Fix Missing Tables</p>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['migrate'])) {
            echo '<div class="output">';
            
            try {
                require_once 'config/database.php';
                
                echo "=== Starting Database Migration ===\n\n";
                
                // Check existing tables
                echo "📋 Checking existing tables...\n";
                $existingTables = [];
                try {
                    $result = $pdo->query("
                        SELECT table_name 
                        FROM information_schema.tables 
                        WHERE table_schema = 'public'
                        ORDER BY table_name
                    ");
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        $existingTables[] = $row['table_name'];
                    }
                    echo "Found " . count($existingTables) . " existing tables: " . implode(', ', $existingTables) . "\n\n";
                } catch(PDOException $e) {
                    echo "⚠️  Warning: Could not check existing tables\n\n";
                }
                
                // Execute schema
                echo "🔧 Executing schema file...\n";
                $sql = file_get_contents('sql/schema-postgresql.sql');
                $pdo->exec($sql);
                echo "✅ Schema executed successfully!\n\n";
                
                // Verify all required tables
                echo "🔍 Verifying required tables...\n";
                $requiredTables = [
                    'users' => 'User accounts',
                    'user_profiles' => 'User profile information',
                    'categories' => 'Product categories',
                    'products' => 'Product listings',
                    'orders' => 'Customer orders',
                    'order_items' => 'Order line items',
                    'carts' => 'Shopping carts',
                    'messages' => 'User messages',
                    'notifications' => 'User notifications',
                    'email_verifications' => 'Email verification tokens'
                ];
                
                $result = $pdo->query("
                    SELECT table_name 
                    FROM information_schema.tables 
                    WHERE table_schema = 'public'
                ");
                $currentTables = [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $currentTables[] = $row['table_name'];
                }
                
                $allPresent = true;
                foreach ($requiredTables as $table => $description) {
                    $exists = in_array($table, $currentTables);
                    $status = $exists ? "✅" : "❌";
                    echo "$status $table - $description\n";
                    if (!$exists) {
                        $allPresent = false;
                    }
                }
                
                echo "\n";
                if ($allPresent) {
                    echo "🎉 SUCCESS! All required tables are present!\n\n";
                    echo "📧 Default admin credentials:\n";
                    echo "   Email: admin@saraplocal.com\n";
                    echo "   Password: password\n\n";
                    echo "✨ Your application is ready to use!\n";
                } else {
                    echo "⚠️  WARNING: Some tables are still missing.\n";
                    echo "Please check the error messages above.\n";
                }
                
            } catch(PDOException $e) {
                echo "❌ ERROR during migration:\n";
                echo $e->getMessage() . "\n\n";
                echo "Stack trace:\n";
                echo $e->getTraceAsString() . "\n";
            } catch(Exception $e) {
                echo "❌ UNEXPECTED ERROR:\n";
                echo $e->getMessage() . "\n";
            }
            
            echo '</div>';
            echo '<button onclick="location.reload()">Run Again</button>';
        } else {
            ?>
            <div class="output info">
This tool will create all missing database tables required for Sarap Local.

It is safe to run multiple times - it will only create tables that don't exist.

Tables that will be created (if missing):
• users - User accounts
• user_profiles - User profile information  
• categories - Product categories
• products - Product listings
• orders - Customer orders
• order_items - Order line items
• carts - Shopping carts
• messages - User messages
• notifications - User notifications ⚠️ (MISSING - causing your error)
• email_verifications - Email verification tokens

Click the button below to start the migration.
            </div>
            
            <form method="POST">
                <button type="submit" name="migrate" value="1">
                    🚀 Run Migration
                </button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>
