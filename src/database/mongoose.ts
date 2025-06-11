import mongoose from 'mongoose';

/**
 * Establishes a connection to MongoDB using Mongoose.
 * @param uri - MongoDB connection string (e.g., from environment variable)
 */
export const connectToDatabase = async (uri: string): Promise<void> => {
  if (!uri) {
    throw new Error('❌ MongoDB connection URI is not provided.');
  }

  try {
    // Connection success
    mongoose.connection.on('connected', () => {
      console.log('🟢 MongoDB connected successfully');
    });

    // Connection error
    mongoose.connection.on('error', (err) => {
      console.error('🔴 MongoDB connection error:', err);
    });

    // Disconnection
    mongoose.connection.on('disconnected', () => {
      console.warn('⚠️ MongoDB disconnected');
    });

    // Graceful shutdown on termination signal
    process.on('SIGINT', async () => {
      await mongoose.connection.close();
      console.log('🔌 MongoDB disconnected due to app termination');
      process.exit(0);
    });

    await mongoose.connect(uri, {} as mongoose.ConnectOptions); // Type assertion to avoid TS complaints
  } catch (error) {
    console.error('❌ Failed to connect to MongoDB:', error);
    throw error;
  }
};
