# Firebase Setup Instructions

## Fix the Current Error

The error you're seeing is because Firebase credentials are not configured. Follow these steps:

### 1. Firebase Console Setup
1. Go to https://console.firebase.google.com/
2. Create a new project or select existing project
3. Go to Project Settings > Service Accounts
4. Click "Generate new private key"
5. Download the JSON file

### 2. Place Service Account File
```bash
# Move the downloaded JSON file to:
mv ~/Downloads/your-project-firebase-adminsdk-xxxxx.json storage/app/firebase/service-account.json

# Set proper permissions
chmod 600 storage/app/firebase/service-account.json
```

### 3. Configure Environment Variables
Add these to your `.env` file:
```env
# Firebase Web SDK Configuration (from Firebase Console > Project Settings > General)
FIREBASE_API_KEY=AIzaSyD_XXXXXXXXXXXXXXXXXXXXXXXXXX
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789012
FIREBASE_APP_ID=1:123456789012:web:XXXXXXXXXXXXXX

# Firebase Admin SDK Path
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account.json

# SMS Test Mode (for development)
FIREBASE_SMS_TEST_MODE=true
FIREBASE_SMS_TEST_NUMBERS=+85512345678:123456,+85587654321:654321
```

### 4. Enable Phone Authentication
1. In Firebase Console > Authentication > Sign-in method
2. Enable "Phone" provider
3. Add your domain to authorized domains

### 5. Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
```

### 6. Test the Setup
Navigate to `http://localhost:8000/password/phone` to test the implementation.

## Development Testing
- Use test phone numbers with `FIREBASE_SMS_TEST_MODE=true`
- Test number: `+85512345678` with code: `123456`

## Production Setup
- Set `FIREBASE_SMS_TEST_MODE=false`
- Remove test numbers from Firebase Console
- Monitor SMS usage in Firebase Console
