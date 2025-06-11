import dotenv from "dotenv";
import { config } from './src/config/config';
import { connectToDatabase } from './src/database/mongoose';

import app from "./src/app";
dotenv.config();

const startServer = async () => {
    try {
        await connectToDatabase(config.db.url); // ✅ Proper DB connect
        app.listen(config.app.port, () => {
            console.log(`🚀 Server running on http://localhost:${config.app.port} in ${config.app.env} mode`);
        });

        process.on('unhandledRejection', (err) => {
            console.error('❌ Unhandled Rejection:', err);
            process.exit(1);
        });

        process.on('uncaughtException', (err) => {
            console.error('❌ Uncaught Exception:', err);
            process.exit(1);
        });

    } catch (error) {
        console.error('❌ Failed to start server:', error);
        process.exit(1);
    }
};

startServer();