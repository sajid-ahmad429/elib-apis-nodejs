// Load environment variables from a .env file using dotenv
import dotenv from 'dotenv';
import path from 'path';

// Load .env file from the project's root directory or a specified location
dotenv.config({ path: path.resolve(__dirname, '../../.env') });

// List of essential environment variables required for the app to run
const requiredEnvVars = ['PORT', 'DATABASE_URL'];

// Check for missing required environment variables
requiredEnvVars.forEach((key) => {
  if (!process.env[key]) {
    throw new Error(`❌ Missing required environment variable: ${key}`);
  }
});

/**
 * Application configuration object.
 * This object is frozen to prevent accidental mutation during runtime.
 */
export const config = Object.freeze({
  app: {
    // Application name (optional, useful for logging or display)
    name: 'FleetTrack-API',

    // Port on which the server will run (defaults to 9009 if not specified)
    port: parseInt(process.env.PORT || '9009', 10),

    // Application environment (e.g., development, production)
    env: process.env.NODE_ENV || 'development',
  },

  db: {
    // Full database connection URL (required)
    url: process.env.DATABASE_URL || '',
  },

  // Uncomment and configure JWT if authentication is needed
  // jwt: {
  //   // Secret key used for signing JWT tokens
  //   secret: process.env.JWT_SECRET || '',

  //   // Token expiration time (e.g., '1h', '7d')
  //   expiresIn: process.env.JWT_EXPIRES_IN || '1h',
  // },
});
