import express, { Request, Response } from "express";
import { notFound, errorHandler } from "./middlewares/errorHandler";
import { getFormattedDate } from "./utils/dateFormatter";
import testRoutes from "./routes/test.route";

const app = express();

// ====== Middlewares ======
app.use(express.json());

// ====== Routes ======
app.use("/api", testRoutes);

// ====== Default Route ======
app.get("/", (req: Request, res: Response) => {
  res.status(200).json({
    success: true,
    message: "✅ API is working!",
    data: {
      time: getFormattedDate(),
    },
  });
});

// ====== 404 Handler ======
app.use(notFound);

// ====== Centralized Error Handler ======
app.use(errorHandler);

export default app;
