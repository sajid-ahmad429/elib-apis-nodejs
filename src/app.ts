import express, { Request, Response } from "express";
import { notFound, errorHandler } from "./middlewares/errorHandler";
import { getFormattedDate } from "./utils/dateFormatter";
import testRoutes from "./routes/test.route";
import usersRoutes from "./routes/users/userRoutes";

const app = express();

// ====== Middlewares ======
app.use(express.json());
// app.use(express.urlencoded({ extended: true }));


// ====== Routes ======
app.use("/api", testRoutes);
app.use("/api/users", usersRoutes);

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
