import express, { Request, Response } from "express";
import { getFormattedDate } from "./utils/dateFormatter"; // Adjust path as needed
import testRoutes from "./routes/test.route";

const app = express();

// Middleware
app.use(express.json());

// Routes
app.use('/api', testRoutes); // ➤ Route available at /api/testing

// Default home
app.get("/", (req: Request, res: Response) => {
    res.status(200).json({
        success: true,
        message: '✅ Testing!',
        data: {
            time: getFormattedDate()
        }
    });
});

export default app;