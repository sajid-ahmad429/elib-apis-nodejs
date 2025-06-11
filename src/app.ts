import express, {Request, Response} from "express";

const app = express();
app.use(express.json());

app.get("/testing", (req: Request, res: Response) => {
    res.status(200).json({
        success: true,
        message: '✅ Testing!',
        data: {
        time: new Date().toISOString()
        }
    });
});

export default app;