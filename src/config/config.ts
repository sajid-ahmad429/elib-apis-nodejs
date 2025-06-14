// Load environment variables from a .env file using dotenv
import dotenv from 'dotenv';
import path from 'path';

// Load .env file from the project's root directory or a specified location
dotenv.config({ path: path.resolve(__dirname, '../../.env') });

// List of essential environment variables required for the app to run
const requiredEnvVars = [
  'PORT',
  'DATABASE_URL',
  'CLOUDINARY_CLOUD_NAME',
  'CLOUDINARY_API_KEY',
  'CLOUDINARY_API_SECRET',
];

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
    name: 'FleetTrack-API',
    port: parseInt(process.env.PORT || '9009', 10),
    env: process.env.NODE_ENV || 'development',
  },

  db: {
    url: process.env.DATABASE_URL || '',
  },

  jwt: {
    secret: process.env.JWT_SECRET || '',
    expiresIn: process.env.JWT_EXPIRES_IN || '1h',
  },

  cloudinary: {
    cloud_name: process.env.CLOUDINARY_CLOUD_NAME!,
    api_key: process.env.CLOUDINARY_API_KEY!,
    api_secret: process.env.CLOUDINARY_API_SECRET!,
  },
});
