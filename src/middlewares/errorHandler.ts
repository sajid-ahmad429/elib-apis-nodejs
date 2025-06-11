import { Request, Response, NextFunction } from "express";
import { HttpError } from "http-errors";
import createHttpError from "http-errors";

// 404 handler
export const notFound = (req: Request, res: Response, next: NextFunction) => {
  // Properly create a 404 error using http-errors
  next(createHttpError(404, `🔍 Not Found - `));
};

// Central error handler
export const errorHandler = (
  err: HttpError,
  req: Request,
  res: Response
) => {
  const statusCode = err.status || 500;

  res.status(statusCode).json({
    success: false,
    statusCode,
    message: err.message || "Internal Server Error",
    stack: process.env.NODE_ENV === "development" ? undefined : err.stack,
  });
};
